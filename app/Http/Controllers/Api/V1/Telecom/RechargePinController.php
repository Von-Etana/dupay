<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Network;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\TraitsFolder\MailTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Models\EpinRate;
use App\Models\EpinPurchase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RechargePinController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }
    
    public function buyEpin(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
    
        // Validation
        $validatedData = Validator::make($data, [
            'network_id' => 'required|exists:networks,id',
            'value' => 'required|numeric|min:50',
            'quantity' => 'required|numeric|min:1',
            'transaction_pin' => 'required'
        ]);
    
        if ($validatedData->fails()) {
            $error = implode(', ', $validatedData->errors()->all());
            return response(['success' => false, 'message' => $error]);
        }
    
        // Transaction Pin Validation (if not API)
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        if ($user->blocked_status == true) {
            return response()->json([
                'success' => false,
                'message' => "Your account has been temporarily blocked due to unusual activity. Please contact support to restore access."
            ]);
        }
    
        $network = Network::find($data['network_id']);
        if (!$network) {
            return response(['success' => false, 'message' => 'Invalid Network']);
        }
    
        // Fetch the rate
        $rate = EpinRate::where('network_id', $data['network_id'])
            ->where('amount', $data['value'])
            ->first();
    
        if (!$rate) {
            return response(['success' => false, 'message' => 'ePIN rate not found for this amount and network.']);
        }
    
        $unit_price = $rate->rate;
        $amount = $unit_price * $data['quantity'];
    
        return DB::transaction(function () use ($user, $amount, $data, $network, $request) {
            
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            
            // Double-check balance after locking
            if ($lockedUser->balance < $amount) {
                return response(['success' => false, 'message' => 'Insufficient Balance']);
            }
    
            $balanceAction = userBalanceAction($lockedUser, $amount, '1', "-");
    
            if (!$balanceAction["success"]) {
                return response(['success' => false, 'message' => 'Insufficient balance. Please refresh and try again.']);
            }
    
            $init_balance = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
            $realPrice = $amount;
            $Trx = strtoupper(Str::random(16));
    
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'https://strowallet.com/api/buy_epin/', [
                    'query' => [
                        'public_key' => env('STROWALLET_PUBLIC_KEY'),
                        'card_network' => $network->name,
                        'value' => $data['value'],
                        'quantity' => $data['quantity'],
                    ],
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'timeout' => 30,
                ]);
    
                $body = json_decode($response->getBody(), true);
                
                Log::info('From Strowallet: ' . json_encode($body));
    
                if (isset($body['status']) && $body['status'] === 'error') {
                    userBalanceAction($lockedUser, $amount, '1', "+");
                    
                    if (str_contains(strtolower($body['message']), 'insufficient balance')) {
                        return response(['success' => false, 'message' => 'Service provider has insufficient balance. Please try again later.']);
                    }
                    return response(['success' => false, 'message' => $body['message'] ?? 'API Error occurred']);
                }
    
                if (!isset($body['status']) || $body['status'] !== 'success') {

                    userBalanceAction($lockedUser, $amount, '1', "+");
                    return response(['success' => false, 'message' => $body['message'] ?? 'Unknown error from service provider']);
                }
    
                try {
                    $transaction = $lockedUser->transaction()->create([
                        "wallet_id" => '1',
                        "user_level_id" => $lockedUser->user_level_id,
                        "service" => $network->name,
                        "service_type" => "Recharge Card Purchase",
                        "description" => strtoupper($network->name) . " ₦{$data['value']} x {$data['quantity']}",
                        "amount" => $amount,
                        "real_price" => $realPrice,
                        "charge" => 0,
                        "phone_number" => null,
                        "type" => "0",
                        "initial_balance" => $init_balance,
                        "final_balance" => $final_balance,
                        "reference" => $Trx,
                        "status" => 'success',
                        'api_reference' => $body['data']['trxid'] ?? null,
                    ]);
    
                    if (!$transaction) {
                        throw new \Exception("Failed to create transaction record");
                    }
    
                    referralCommission($lockedUser, $transaction, $amount);
    
                    if (getSiteSettings("phone_notify") == "Yes") {
                        $smsDescription = "Txn: Debit\n" .
                            "Amt: NGN " . number_format($amount, 2) . "\n" .
                            "Des: {$network->name} ePIN Purchase {$Trx}\n" .
                            "Qty: {$data['quantity']} x ₦{$data['value']}\n" .
                            "Date: " . now()->format('d-m-Y H:i') . "\n" .
                            "Bal: NGN " . number_format($final_balance, 2);
    
                        sendTransactionSms($lockedUser, $smsDescription, '1');
                    }
    
                    if (getSiteSettings("email_notify") == "Yes") {
                        $this->sendEmailNotification($lockedUser, $network, $data, $amount, $final_balance, $Trx);
                    }
    
                    $cards = $body['data']['cards'] ?? [];
                    
                    if (empty($cards)) {
                        throw new \Exception("No cards received from API");
                    }
    
                    $pdf = Pdf::loadView('pdf.epins', [
                        'network' => strtoupper($network->name),
                        'value' => $data['value'],
                        'quantity' => $data['quantity'],
                        'cards' => $cards,
                        'reference' => $Trx,
                        'user' => $lockedUser,
                        'date' => now(),
                    ]);
    
                    $pdfPath = "epins/epin_{$Trx}.pdf";
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                    $pdfUrl = Storage::url($pdfPath);
    
                    EpinPurchase::create([
                        'user_id' => $lockedUser->id,
                        'network_id' => $request->network_id,
                        'value' => $data['value'],
                        'quantity' => $data['quantity'],
                        'cards' => json_encode($cards),
                        'reference' => $Trx,
                        'pdf_path' => $pdfUrl,
                    ]);
    
                    if (!empty($lockedUser->fcmToken)) {
                        sendFcmNotification(
                            $lockedUser->fcmToken,
                            'ePINs successfully purchased',
                            'Recharge card purchase successful! Your ePINs have been generated and saved. You can download the PDF receipt.',
                            ['user_id' => $lockedUser->id, 'type' => 'success']
                        );
                    }
    
                    return response([
                        'success' => true,
                        'message' => 'Recharge card purchase successful! Your ePINs have been generated and saved. You can download the PDF receipt.',
                        'data' => $body['data'],
                    ]);
    
                } catch (\Exception $e) {
                    
                    \Log::error('Transaction Save Error: ' . $e->getMessage());
                    
                    userBalanceAction($lockedUser, $amount, '1', "+");
                    
                    return response(['success' => false, 'message' => 'Transaction failed. Your balance has been refunded. Please try again.']);
                }
    
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                userBalanceAction($lockedUser, $amount, '1', "+");
                
                $response = $e->getResponse();
                $body = json_decode($response->getBody()->getContents(), true);
    
                if ($response->getStatusCode() === 400 && isset($body['message'])) {
                    if (str_contains(strtolower($body['message']), 'insufficient balance')) {
                        return response(['success' => false, 'message' => 'Service provider has insufficient balance. Please try again later.']);
                    }
                    return response(['success' => false, 'message' => $body['message']]);
                }
    
                return response(['success' => false, 'message' => 'Service temporarily unavailable. Please try again later.']);
    
            } catch (\Exception $e) {
                userBalanceAction($lockedUser, $amount, '1', "+");
                
                \Log::error('ePIN Purchase Error: ' . $e->getMessage());
                return response(['success' => false, 'message' => 'An error occurred while processing your request. Please try again later.']);
            }
            
        }, 5); 
    }
    
    private function sendEmailNotification($user, $network, $data, $amount, $final_balance, $Trx)
    {
        $formattedDate = now()->format('d-M-Y h:i A');
        $formattedAmount = number_format($amount, 2);
        $formattedBalance = number_format($final_balance, 2);
        $networkName = $network->name;
        $transactionRef = $Trx;
        $totalValue = $data['value'] * $data['quantity'];
    
        $message = "
            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
            <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                    <td style='padding:8px; border:1px solid #ddd;'>DEBIT (ePIN Purchase)</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Network</td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$networkName}</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Card Value</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦{$data['value']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Quantity</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$data['quantity']}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Total Value</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($totalValue, 2) . "</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Amount Debited</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$transactionRef}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedBalance}</td>
                </tr>
            </table>
            <br>
            <p>Your ePINs have been generated successfully. You can download the PDF receipt from your dashboard.</p>
            <p>Thank you for using Dupay.</p>
        ";
    
        try {
            $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification - ePIN Purchase', $message);
        } catch (\Throwable $th) {
            \Log::error('Email sending failed: ' . $th->getMessage());
        }
    }
    
    // public function buyEpin(Request $request)
    // {
    //     $user = auth()->user();
    //     $data = $request->all();
    
    //     // Validation
    //     $validatedData = Validator::make($data, [
    //         'network_id' => 'required|exists:networks,id',
    //         'value' => 'required|numeric|min:50',
    //         'quantity' => 'required|numeric|min:1',
    //         'transaction_pin' => 'required'
    //     ]);
    
    //     if ($validatedData->fails()) {
    //         $error = implode(', ', $validatedData->errors()->all());
    //         return response(['success' => false, 'message' => $error]);
    //     }
    
    //     // Transaction Pin Validation (if not API)
    //     if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
    //         if (!$request->transaction_pin) {
    //             return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
    //         } elseif (!$user->transaction_pin) {
    //             return response(['success' => false, 'message' => "Transaction Pin not set"]);
    //         } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
    //             return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
    //         }
    //     }
        
    //     if($user->blocked_status == true){
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Your account has been temporarily blocked due to unusual activity. Please contact support to restore access."
    //         ]);
    //     }
    
    //     $network = Network::find($data['network_id']);
    //     if (!$network) {
    //         return response(['success' => false, 'message' => 'Invalid Network']);
    //     }
    
    //     // Fetch the rate
    //     $rate = EpinRate::where('network_id', $data['network_id'])
    //         ->where('amount', $data['value'])
    //         ->first();
    
    //     if (!$rate) {
    //         return response(['success' => false, 'message' => 'ePIN rate not found for this amount and network.']);
    //     }
    
    //     $unit_price = $rate->rate;
    //     $amount = $unit_price * $data['quantity'];
    
    //     // Check user balance BEFORE making API call
    //     if ($user->balance < $amount) {
    //         return response(['success' => false, 'message' => 'Insufficient Balance']);
    //     }
    
    //     // Call API to purchase ePIN FIRST before deducting balance
    //     try {
    //         $client = new \GuzzleHttp\Client();
    //         $response = $client->request('POST', 'https://strowallet.com/api/buy_epin/', [
    //             'query' => [
    //                 'public_key' => env('STROWALLET_PUBLIC_KEY'),
    //                 'card_network' => $network->name,
    //                 'value' => $data['value'],
    //                 'quantity' => $data['quantity'],
    //             ],
    //             'headers' => [
    //                 'accept' => 'application/json',
    //             ],
    //             'timeout' => 30, // Add timeout
    //         ]);
    
    //         $body = json_decode($response->getBody(), true);
    
    //         // Check API response for insufficient balance
    //         if (isset($body['status']) && $body['status'] === 'error') {
    //             if (str_contains(strtolower($body['message'] ?? ''), 'insufficient balance')) {
    //                 return response(['success' => false, 'message' => 'Service provider has insufficient balance. Please try again later.']);
    //             }
    //             return response(['success' => false, 'message' => $body['message'] ?? 'API Error occurred']);
    //         }
    
    //         if (!isset($body['status']) || $body['status'] !== 'success') {
    //             return response(['success' => false, 'message' => $body['message'] ?? 'Unknown error from service provider']);
    //         }
    
    //     } catch (\GuzzleHttp\Exception\ClientException $e) {
    //         $response = $e->getResponse();
    //         $body = json_decode($response->getBody()->getContents(), true);
            
    //         if ($response->getStatusCode() === 400 && isset($body['message'])) {
    //             if (str_contains(strtolower($body['message']), 'insufficient balance')) {
    //                 return response(['success' => false, 'message' => 'Service provider has insufficient balance. Please try again later.']);
    //             }
    //             return response(['success' => false, 'message' => $body['message']]);
    //         }
            
    //         return response(['success' => false, 'message' => 'Service temporarily unavailable. Please try again later.']);
    //     } catch (\Exception $e) {
    //         \Log::error('ePIN Purchase Error: ' . $e->getMessage());
    //         return response(['success' => false, 'message' => 'An error occurred while processing your request. Please try again later.']);
    //     }
    
    //     // Only deduct balance after successful API call
    //     $balanceAction = userBalanceAction($user, $amount, '1', "-");
    
    //     if (!$balanceAction["success"]) {
    //         return response(['success' => false, 'message' => 'Insufficient balance. Please refresh and try again.']);
    //     }
    
    //     $Trx = strtoupper(Str::random(16));
    //     $init_balance = $balanceAction["init_balance"];
    //     $final_balance = $balanceAction["final_balance"];
    //     $realPrice = $amount;
    
    //     // Save transaction
    //     try {
    //         $transaction = $user->transaction()->create([
    //             "wallet_id" => '1',
    //             "user_level_id" => $user->user_level_id,
    //             "service" => $network->name,
    //             "service_type" => "Recharge Card Purchase",
    //             "description" => strtoupper($network->name) . " ₦{$data['value']} x {$data['quantity']}",
    //             "amount" => $amount,
    //             "real_price" => $realPrice,
    //             "charge" => 0,
    //             "phone_number" => null,
    //             "type" => "0",
    //             "initial_balance" => $init_balance,
    //             "final_balance" => $final_balance,
    //             "reference" => $Trx,
    //             "status" => 'success',
    //             'api_reference' => $body['data']['trxid'],
    //         ]);
    
    //         $trans_error = $transaction ? "" : "There is error";
    
    //         if ($transaction) {
    //             referralCommission($user, $transaction);
    //         }
    
    //         if (getSiteSettings("phone_notify") == "Yes") {
    //             $smsDescription = "Txn: Debit\n" .
    //               "Amt: NGN " . number_format($amount, 2) . "\n" .
    //               "Des: {$network->name} ePIN Purchase {$Trx}\n" .
    //               "Qty: {$data['quantity']} x ₦{$data['value']}\n" .
    //               "Date: " . now()->format('d-m-Y H:i') . "\n" .
    //               "Bal: NGN " . number_format($final_balance, 2);
                
    //             sendTransactionSms($user, $smsDescription, '1');
    //         }
    
    //         if (getSiteSettings("email_notify") == "Yes") {
    //             $formattedDate = now()->format('d-M-Y h:i A');
    //             $formattedAmount = number_format($amount, 2);
    //             $formattedBalance = number_format($final_balance, 2);
    //             $networkName = $network->name;
    //             $transactionRef = $Trx;
    //             $totalValue = $data['value'] * $data['quantity'];
                
    //             $message = "
    //                 <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
    //                 <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>DEBIT (ePIN Purchase)</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Network</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'><strong>{$networkName}</strong></td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Card Value</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>₦{$data['value']}</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Quantity</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>{$data['quantity']}</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Total Value</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($totalValue, 2) . "</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Amount Debited</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>{$transactionRef}</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Transaction Description</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>ePIN purchase of {$data['quantity']} x ₦{$data['value']} {$networkName} cards</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
    //                     </tr>
    //                     <tr>
    //                         <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
    //                         <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedBalance}</td>
    //                     </tr>
    //                 </table>
    //                 <br>
    //                 <p>Your ePINs have been generated successfully. You can download the PDF receipt from your dashboard.</p>
    //                 <p>Thank you for using Dupay.</p>
    //             ";
                
    //             try {
    //                 $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification - ePIN Purchase', $message);
    //             } catch (\Throwable $th) {
    //                 \Log::error('Email sending failed: ' . $th->getMessage());
    //             }
    //         }
    
    //         $cards = $body['data']['cards'];
    
    //         // Generate PDF
    //         $pdf = Pdf::loadView('pdf.epins', [
    //             'network' => strtoupper($network->name),
    //             'value' => $data['value'],
    //             'quantity' => $data['quantity'],
    //             'cards' => $cards,
    //             'reference' => $Trx,
    //             'user' => $user,
    //             'date' => now(),
    //         ]);
    
    //         $pdfPath = "epins/epin_{$Trx}.pdf";
    //         Storage::disk('public')->put($pdfPath, $pdf->output());
    //         $pdfUrl = Storage::url($pdfPath);
    
    //         EpinPurchase::create([
    //             'user_id' => $user->id,
    //             'network_id' => $request->network_id,
    //             'value' => $data['value'],
    //             'quantity' => $data['quantity'],
    //             'cards' => json_encode($cards),
    //             'reference' => $Trx,
    //             'pdf_path' => $pdfUrl,
    //         ]);
    
    //         // (new FirebaseUserUpdate)->addUserNotification($user,
    //         //     [
    //         //         "message" => "ePIN purchase successful for {$network->name} ₦{$data['value']} x {$data['quantity']}",
    //         //         "type" => "transaction",
    //         //         "read" => 0,
    //         //         "data" => [
    //         //             "service" => 'Recharge Card Purchase',
    //         //             "id" => $transaction->id,
    //         //             "reference_id" => $Trx,
    //         //             "status" => 'success',
    //         //             'date' => now()
    //         //         ]
    //         //     ]
    //         // );
    
    //         // Update Firebase user data
    //         // (new FirebaseUserUpdate)->updateUser($user);
    
    //         // Send FCM notification
    //         if (!empty($user->fcmToken)) {
    //             sendFcmNotification(
    //                 $user->fcmToken,
    //                 'ePINs successfully purchased',
    //                 'Recharge card purchase successful! Your ePINs have been generated and saved. You can download the PDF receipt.',
    //                 ['user_id' => $user->id, 'type' => 'success']
    //             );
    //         }
    
    //         return response([
    //             'success' => true,
    //             'message' => 'Recharge card purchase successful! Your ePINs have been generated and saved. You can download the PDF receipt.',
    //             // 'pdf_url' => $pdfUrl, 
    //             // 'date' => now(),
    //             // 'amount_debited' => $amount,
    //             'data' => $body['data'], 
                 
    //         ]);
    
    //     } catch (\Exception $e) {
    //         \Log::error('Transaction Save Error: ' . $e->getMessage());
            
    //         // Refund user if transaction saving fails
    //         userBalanceAction($user, $amount, '1', "+");
            
    //         return response(['success' => false, 'message' => 'Transaction failed. Your balance has been refunded. Please try again.']);
    //     }
    // }
    
    public function fetchEpins(Request $request)
    {
        $user = auth()->user();

        $epins = EpinPurchase::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Recharge PINs fetched successfully',
            'data' => $epins
        ]);
    }
    
    public function trysms(Request $request)
    {
    // Step 1: Validate phone number
    $validator = Validator::make($request->all(), [
        'phone_number' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => implode(', ', $validator->errors()->all()),
        ], 400);
    }

    $user = auth()->user();
    $smsCharge = 10; // Set your SMS charge here
    $message = "Your Dupay verification code 557797";

    try {
        // Step 2: Send SMS via StroWallet API
        $client = new Client();
        $response = $client->request('POST', 'https://strowallet.com/api/bulk-sms/', [
            'query' => [
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                'sender_id'  => 'Dupay',
                'message'    => $message,
                'phone'      => $request->phone_number,
            ],
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        Log::info('SMS API Response', [
            'status_code' => $statusCode,
            'response' => $body
        ]);

        // Step 3: Ensure the API reports success
        if (
            $statusCode !== 200 ||
            !isset($body['api_response']['success']) ||
            $body['api_response']['success'] !== true
        ) {
            return response()->json([
                'success' => false,
                'message' => $body['api_response']['message'] ?? 'SMS API returned failure.',
            ], 502);
        }

        // Step 4: Check user wallet balance
        if (!checkUserWallet($user, $smsCharge, "1")) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance.',
            ], 402);
        }

        // Step 5: Deduct balance
        $balanceAction = userBalanceAction($user, $smsCharge, '1', "-");
        $init_balance = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];
        $reference = strtoupper(Str::random(16));

        // Step 6: Record the transaction
        $user->transaction()->create([
            'wallet_id'         => $user->wallet_id ?? 1, // Replace with correct wallet ID logic
            'user_level_id'     => $user->user_level_id,
            'service'           => 'SMS Notification',
            'api_connection_id' => null,
            'service_type_id'   => null,
            'service_plan_id'   => null,
            'service_type'      => 'Notification',
            'description'       => "SMS Notification charge for transaction alert to {$user->phone}",
            'amount'            => $smsCharge,
            'real_price'        => $smsCharge,
            'charge'            => 0,
            'phone_number'      => $user->phone,
            'type'              => '0',
            'initial_balance'   => $init_balance,
            'final_balance'     => $final_balance,
            'reference'         => $reference,
            'status'            => 'successful',
        ]);

        // Step 7: Return success response
        return response()->json([
            'success' => true,
            'message' => 'SMS sent successfully.',
        ]);

    } catch (\Exception $e) {
        Log::error('SMS sending failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while sending SMS. Please try again later.',
        ], 500);
    }
}

    public function getJson()
    {
        $token = getSafeHavenAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Token generation failed.'];
        }
        
        return response()->json([
            'success' => true,
            'token' => $token,
        ], 200);
    }

}
