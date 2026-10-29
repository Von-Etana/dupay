<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Data;
use App\Models\Telecom\Network;
use App\Models\Telecom\Offer;
use App\Models\Telecom\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use iSmartBonus;

class DataController extends Controller
{
    use MailTrait;


    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *          Buy Data
     *
     *
     */
    public function getDataNetwork()
    {
        $item = Network::where("status", "1")
            ->with("dataType")
            ->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No available Data"]);
        }

        return response(['success' => true, "data" => $item]);
    }
    
    public function buyData(Request $request)
{
    $date = Carbon::now();
    $data = $request->all();
    
    // Validation
    $validatedData = Validator::make($data, [
        'wallet_id' => '',
        'phone_number' => 'required',
        'network_id' => 'required',
        'plan_id' => 'required',
        'dataType_id' => '',
    ]);

    if ($validatedData->fails()) {
        $error = "";
        foreach ($validatedData->errors()->all() as $key => $value) {
            $error .= $value;
        }
        return response(['success' => false, 'message' => $error]);
    }

    $user = Auth::user();
    $finalStatus = "pending";

    // Transaction pin validation (keep existing logic)
    if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
    if($user->blocked_status == true){
        return response()->json([
            'success' => false,
            'message' => "Your account has been temporarily blocked due to unusual activity. Please contact support to restore access."
        ]);
    }

    $select_wallet_id = $request->wallet_id ?? "1";
    if (!checkWalletEnable("Data", $select_wallet_id)) {
        return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
    }

    // Get data plan
    $dataPlan = Data::where("id", $request->plan_id)
        ->where("network_id", $request->network_id)
        ->where("data_type_id", $request->dataType_id)
        ->first();

    if (!$dataPlan) {
        return response(['success' => false, 'message' => "Plan Does not Exist"]);
    }

    $amount = $dataPlan->price;

    // Check offer
    $checkOffer = Offer::where("service_type", "DATA")
        ->where("plan_id", $dataPlan->id)
        ->where("user_level_id", $user->user_level_id)
        ->where("status", "1")
        ->first();
    
    if ($checkOffer) {
        $amount = $checkOffer->amount;
    }

    // Check user wallet
    $checkWallet = checkUserWallet($user, $amount, $select_wallet_id);
    if (!$checkWallet) {
        return response(['success' => false, 'message' => "Insufficient Funds"]);
    }

    $balanceAction = userBalanceAction($user, $amount, $select_wallet_id, "-");
    $init_balance = $balanceAction["init_balance"];
    $final_balance = $balanceAction["final_balance"];

    if (!$balanceAction["success"]) {
        return response(['success' => false, 'message' => "Wallet balance error"]);
    }

    // Generate transaction reference
    $Trx = strtoupper(Str::random(16));
    
    // Create transaction record
    $description = $dataPlan->networks->name . " " . $dataPlan->dataType->name . " " . $dataPlan->name . " to $request->phone_number";
    
    $ul = [
        'user_level_id' => $user->user_level_id,
        'service' => $dataPlan->networks->name,
        'service_type_id' => $request->dataType_id,
        'service_plan_id' => $request->plan_id,
        'wallet_id' => $select_wallet_id,
        'service_type' => "Data Topup",
        'plan_size' => $dataPlan->size,
        'real_price' => $dataPlan->real_price,
        'charge' => $amount - $dataPlan->real_price,
        'description' => $description,
        'amount' => $amount,
        'phone_number' => $request->phone_number,
        'type' => "0",
        'initial_balance' => $init_balance,
        'final_balance' => $final_balance,
        'reference' => $Trx,
        'created_at' => $date,
        'status' => $finalStatus,
    ];

    $transaction = $user->transaction()->create($ul);

    // VTPass Integration using your existing authentication system
    $success = false;
    $vtpassResponse = null;
    $vtpassMessage = null;

    try {
        // Get the API connection for VTPass data type
        $apiConnection = $dataPlan->dataType->apiConnection;
        
        if (!$apiConnection || $apiConnection->status == "0") {
            throw new \Exception("VTPass connection not available");
        }

        // Map network names to VTPass service IDs
        $networkMapping = [
            'MTN' => 'mtn-data',
            'Glo' => 'glo-data',
            'Airtel' => 'airtel-data',
            '9mobile' => 'etisalat-data',
        ];

        $serviceID = $networkMapping[$dataPlan->networks->name] ?? null;
        
        if (!$serviceID) {
            throw new \Exception("Network not supported for VTPass integration");
        }

        // Prepare VTPass request using your existing header system
        $Myheader = getHeaderApiConnection($apiConnection);
        $header = $Myheader["header"];
        $token = $Myheader["token"];

        // Prepare payload based on your existing system
        $apiPayLoad = json_decode($apiConnection->payload, true);
        $payload = [];
        
        if ($apiPayLoad != null && is_array($apiPayLoad)) {
            foreach ($apiPayLoad as $key => $value) {
                $value = str_replace("{{planId}}", $dataPlan->id, $value);
                $value = str_replace("{{planCode}}", $dataPlan->code, $value);
                $value = str_replace("{{planExtraCode}}", $dataPlan->extra, $value);
                $value = str_replace("{{planSize}}", $dataPlan->size, $value);
                $value = str_replace("{{networkId}}", $dataPlan->networks->id, $value);
                $value = str_replace("{{networkCode}}", $dataPlan->networks->code, $value);
                $value = str_replace("{{networkName}}", $dataPlan->networks->name, $value);
                $value = str_replace("{{networkOtherName}}", $dataPlan->networks->other_name, $value);
                $value = str_replace("{{dataTypeId}}", $dataPlan->dataType->id, $value);
                $value = str_replace("{{dataTypeName}}", $dataPlan->dataType->name, $value);
                $value = str_replace("{{phoneNumber}}", $request->phone_number, $value);
                $value = str_replace("{{amount}}", $amount, $value);
                $value = str_replace("{{token}}", $token, $value);
                $value = str_replace("{{referenceNumber}}", $Trx, $value);
                $value = str_replace("{{current_date}}", date("Ymdhis"), $value);
                $value = str_replace("{{serviceID}}", $serviceID, $value);
                $value = str_replace("{{variation_code}}", $dataPlan->variation_code, $value);
                $value = str_replace("{{billersCode}}", $request->phone_number, $value);
                $payload[$key] = $value;
            }
        } else {
            // Fallback payload if no payload configured
            $payload = [
                'request_id' => $Trx,
                'serviceID' => $serviceID,
                'billersCode' => $request->phone_number,
                'variation_code' => $dataPlan->variation_code,
                'amount' => (int) $amount,
                'phone' => $request->phone_number,
            ];
        }

        $endpoint = $apiConnection->url;

        // Make API call using your existing methods
        if ($apiConnection->method == "0") {
            // GET method
            $endpoint = str_replace("{{planId}}", $dataPlan->id, $endpoint);
            $endpoint = str_replace("{{planCode}}", $dataPlan->code, $endpoint);
            $endpoint = str_replace("{{planExtraCode}}", $dataPlan->extra, $endpoint);
            $endpoint = str_replace("{{planSize}}", $dataPlan->size, $endpoint);
            $endpoint = str_replace("{{networkId}}", $dataPlan->networks->id, $endpoint);
            $endpoint = str_replace("{{networkCode}}", $dataPlan->networks->code, $endpoint);
            $endpoint = str_replace("{{networkName}}", $dataPlan->networks->name, $endpoint);
            $endpoint = str_replace("{{networkOtherName}}", $dataPlan->networks->other_name, $endpoint);
            $endpoint = str_replace("{{dataTypeId}}", $dataPlan->dataType->id, $endpoint);
            $endpoint = str_replace("{{dataTypeName}}", $dataPlan->dataType->name, $endpoint);
            $endpoint = str_replace("{{phoneNumber}}", $request->phone_number, $endpoint);
            $endpoint = str_replace("{{amount}}", $amount, $endpoint);
            $endpoint = str_replace("{{token}}", $token, $endpoint);
            $endpoint = str_replace("{{referenceNumber}}", $Trx, $endpoint);
            $endpoint = str_replace("{{current_date}}", date("Ymdhis"), $endpoint);
            $endpoint = str_replace("{{serviceID}}", $serviceID, $endpoint);
            $endpoint = str_replace("{{variation_code}}", $dataPlan->variation_code, $endpoint);
            
            $vtpassResponse = getHttp($endpoint, $header);
        } else {
            // POST method
            if ($apiConnection->method == "1") {
                $vtpassResponse = postHttp($payload, $endpoint, $header);
            } else {
                $vtpassResponse = postHttpParameter($endpoint, $payload, $header);
            }
        }

        // Check VTPass response using your existing success field system
        if ($vtpassResponse != null) {
            $apiSuccessField = $apiConnection->success_field;
            $apiSuccessResponse = $apiConnection->success_response;
            
            $reponseVal = $vtpassResponse;
            foreach (explode('->', $apiSuccessField) as $itemS) {
                $reponseVal = $reponseVal->$itemS;
            }
            
            if ($reponseVal == $apiSuccessResponse) {
                $success = true;
                $finalStatus = "success";
                $vtpassMessage = $vtpassResponse->response_description ?? 'Data purchase successful';
            } else {
                $success = false;
                $finalStatus = "failed";
                $vtpassMessage = $vtpassResponse->response_description ?? 'Data purchase failed';
            }
        } else {
            throw new \Exception("No response from VTPass API");
        }

    } catch (\Exception $e) {
        $success = false;
        $finalStatus = "failed";
        $vtpassMessage = "VTPass API Error: " . $e->getMessage();
        
        // Log the error for debugging
        \Log::error('VTPass API Error: ' . $e->getMessage());
    }

    // Update transaction based on VTPass response
    $updateData = [
        'status' => $finalStatus,
        'message' => $vtpassMessage,
    ];

    $user->transaction()->where(['id' => $transaction->id])->update($updateData);

    if ($success) {
        referralCommission($user, $transaction, $amount);
        
        // Handle successful transaction
        if ($dataPlan->promo_id != null) {
            $proT = [
                'promo_id' => $dataPlan->promo_id,
                'reference' => $Trx,
            ];
            $user->promoTransaction()->create($proT);
        }

        // Format SMS message
        $smsDescription = "Txn: Debit\n" .
                         "Amt: NGN " . number_format($amount, 2) . "\n" .
                         "Des: DATA TOPUP {$request->phone_number} {$Trx}\n" .
                         "Date: " . now()->format('d-m-Y H:i') . "\n" .
                         "Bal: NGN " . number_format($final_balance, 2);

        $successDescription = "{$dataPlan->networks->name} {$dataPlan->dataType->name} {$dataPlan->name} to {$request->phone_number} Reference ID Is : #{$Trx}";

        // Send notifications
        if (getSiteSettings("email_notify") == "Yes") {
            try {
                $maskedPhone = substr($request->phone_number, 0, 4) . '***' . substr($request->phone_number, -3);
                $formattedDate = now()->format('d-M-Y h:i A');
                $formattedAmount = number_format($amount, 2);
                $formattedBalance = number_format($final_balance, 2);
                $networkName = $dataPlan->networks->name;
                $transactionRef = $Trx;
                $transactionType = "Data Purchase";
                $planName = $dataPlan->name;
                $planSize = $dataPlan->size;
                
                $message = "
                    <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>DEBIT (Data Purchase)</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Network</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$networkName}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Data Plan</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$planName} ({$planSize})</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Phone Number</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$maskedPhone}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Amount</td>
                            <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                            <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$transactionRef}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Description</td>
                            <td style='padding:8px; border:1px solid #ddd;'>Data purchase of {$planName} on {$networkName}</td>
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
                    <p>Thank you for using Dupay.</p>
                ";
                
                $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification', $message);
            } catch (\Throwable $th) {
                // Log error
            }
        }

        (new FirebaseUserUpdate)->addUserNotification($user, [
            "message" => $successDescription,
            "type" => "transaction",
            "read" => 0,
            "data" => [
                "service" => 'Data Topup',
                "id" => $transaction->id,
                "reference_id" => $Trx,
                "status" => $finalStatus,
                'date' => $date
            ]
        ]);

        if (getSiteSettings("phone_notify") == "Yes") {
            try {
                sendTransactionSms($user, $smsDescription, 1);
            } catch (\Throwable $th) {
                // Log error
            }
        }

        // Update Firebase and process referral commission
        (new FirebaseUserUpdate)->updateUser($user);

        return response()->json([
            'success' => true,
            'code' => 200,
            'status' => 'successful',
            'data' => [
                'service' => "Data Topup",
                'amount' => $amount,
                'plan' => $dataPlan->name,
                'phone_number' => $request->phone_number,
                'date' => $date,
                'message' => $vtpassMessage,
                'reference' => $Trx,
                'network' => $dataPlan->networks->name,
                'wallet_balance' => $final_balance,
            ],
            'user' => $user
        ]);

    } else {
        // Handle failed transaction - refund user
        $balanceAction = userBalanceAction($user, $amount, $select_wallet_id, "+");
        $refund_init_balance = $balanceAction["init_balance"];
        $refund_final_balance = $balanceAction["final_balance"];

        if ($balanceAction["success"]) {
            $refundDescription = "{$dataPlan->networks->name} {$dataPlan->dataType->name} {$dataPlan->name} to {$request->phone_number} Refund";
            $refundTrx = strtoupper(Str::random(16));
            
            $refundData = [
                'user_level_id' => $user->user_level_id,
                'service' => $dataPlan->networks->name,
                'service_type_id' => $request->dataType_id,
                'service_plan_id' => $request->plan_id,
                'wallet_id' => $select_wallet_id,
                'service_type' => "Data Topup",
                'description' => $refundDescription,
                'amount' => $amount,
                'phone_number' => $request->phone_number,
                'type' => "1",
                'initial_balance' => $refund_init_balance,
                'final_balance' => $refund_final_balance,
                'reference' => $refundTrx,
                'reference_ref' => $Trx,
                'status' => "refund",
                'created_at' => $date,
            ];
            
            $user->transaction()->create($refundData);

            (new FirebaseUserUpdate)->addUserNotification($user, [
                "message" => $refundDescription,
                "type" => "transaction",
                "read" => 0,
                "data" => [
                    "service" => 'Data Topup',
                    "id" => $transaction->id,
                    "reference_id" => $Trx,
                    "status" => "refund",
                    'date' => $date
                ]
            ]);
        }

        (new FirebaseUserUpdate)->updateUser($user);

        return response()->json([
            'success' => false,
            'status' => 'unsuccessful',
            'message' => $vtpassMessage,
            'data' => [
                'reference' => $Trx,
                'date' => $date
            ],
        ]);
    }
}
    
//     public function buyData(Request $request)
//     {
//         $date = Carbon::now();
//         $data = $request->all();
//         $validatedData  = Validator::make($data, [
//             'wallet_id' => '',
//             'phone_number' => 'required',
//             'network_id' => 'required',
//             'plan_id' => 'required',
//             'dataType_id' => '',
//         ]);

//         if ($validatedData->fails()) {
//             $error = "";
//             foreach ($validatedData->errors()->all() as $key => $value) {
//                 $error .= $value;
//             }
//             return response(['success' => false, 'message' => $error]);
//         }

//         $user = Auth::user();
//         $finalStatus = "pending";

//           //check transaction pin
//           if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" ) {
//             if(request("fingerPrint") == "" || request("fingerPrint") != true){
//             if ($request->transaction_pin == null || $request->transaction_pin == "") {
//                 return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
//             }
//             elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
//                 return response(['success' => false, 'message' => "Transaction Pin not set"]);
//             }
//             elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
//                 return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
//             }
//             }
//         }

//         $select_wallet_id = $request->wallet_id ?? "1";
//         if (!checkWalletEnable("Data", $select_wallet_id)) {
//             return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
//         }

//         $dataPlan = Data::where("id", $request->plan_id)
//         ->where("network_id", $request->network_id)
//         ->where("data_type_id", $request->dataType_id)
//         ->first();
        
        

//     //     $dataPlan = Data::where("id", $request->plan_id)
//     // ->where("network_id", $request->network_id)
//     // ->where("data_type_id", $request->dataType_id)
//     // ->first();

// if (!$dataPlan) {
//     return response(['success' => false, 'message' => "Plan Does not Exist"]);
// }
//         $amount = $dataPlan->price;



//          //check offer
//          $checkOffer = Offer::where("service_type", "DATA")->where("plan_id", $dataPlan->id)->where("user_level_id", $user->user_level_id)
//          ->where("status", "1")->first();
//          if($checkOffer){
//             $amount = $checkOffer->amount;
//          }



//         //check if promo
//         if ($dataPlan->promo_id != null) {
//             $checkPromo = Promo::find($dataPlan->promo_id);

//             //check if the promo exist
//             if (!$checkPromo) {
//                 return response(['success' => false, 'message' => "Promo Does not Exist"]);
//             }

//             //check if the promo is active
//             if ($checkPromo->status != "1") {
//                 return response(['success' => false, 'message' => "Sorry this Promo is not active"]);
//             }

//             //check if the promo is has started or expire
//             $start_date = Carbon::parse($checkPromo->start_at)
//                 ->toDateTimeString();

//             if ($start_date > Carbon::now()) {
//                 $tt = Carbon::parse($start_date)->diffForHumans();
//                 return response(['success' => false, 'message' => "Promo starts " . $tt]);
//             }

//             $end_date = Carbon::parse($checkPromo->end_at)
//                 ->toDateTimeString();

//             if ($end_date < Carbon::now()) {
//                 $tt = Carbon::parse($end_date)->diffForHumans();
//                 return response(['success' => false, 'message' => "Promo Ends " . $tt]);
//             }

//             //check promo wallet
//             if ($select_wallet_id != $checkPromo->wallet_id) {
//                 return response(['success' => false, 'message' => "Selected wallet not allowed for this promo"]);
//             }

//              //check if the user is using app
//              if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "app") {
//                 return response(['success' => false, 'message' => "this promo is for those using our official mobile app"]);
//             }


//             //check if already used the promo
//             $promoTransactionCount = $user->promoTransaction()->where("promo_id", $checkPromo->id)->count();
//             //check number of times allowed
//             if ($checkPromo->times != "0" && ($promoTransactionCount >= $checkPromo->times)) {
//                 return response(['success' => false, 'message' => "You cant use this offer more than " . $checkPromo->times . " time"]);
//             }


//             //check fund
//             if (((int)$checkPromo->min_amount) > 0) {
//                 //check if its wallet minimum
//                 if($checkPromo->min_amount_type == "1") {
//                         //wallet
//                         $checkWallet =  checkUserWallet($checkPromo->min_amount, $select_wallet_id);
//                         if (!$checkWallet) {
//                             return response(['success' => false, 'message' => "To qualify for this promo you need to have minimum of N" . number_format($checkPromo->min_amount) . " in your wallet"]);
//                         }
//                 }elseif($checkPromo->min_amount_type == "2"){
//                         //deposit
//                         $count = $promoTransactionCount + 1;
//                         $deposit = $user->transaction()->where('type', "1")->where("service_type", "Wallet Funding")->whereDate('created_at', Carbon::today())->get();
//                         if (count($deposit) < 1) {
//                             return response(['success' => false, 'message' => "To qualify for this promo you need minimum deposit of N" . number_format($checkPromo->min_amount * $count)]);
//                         }
//                         if ($deposit->sum("amount") < ($checkPromo->min_amount * $count)) {
//                             return response(['success' => false, 'message' => "To qualify for this promo you need minimum deposit of N" . number_format($checkPromo->min_amount * $count)]);
//                         }
//                     }else{
//                         //transaction
//                         $count = $promoTransactionCount + 1;
//                         $trans = $user->transaction()->where('type', "2")->whereDate('created_at', Carbon::today())->get();
//                         if (count($trans) < 1) {
//                             return response(['success' => false, 'message' => "To qualify for this promo you need to make minimum transaction of N" . number_format($checkPromo->min_amount * $count)]);
//                         }
//                         if ($trans->sum("amount") < ($checkPromo->min_amount * $count)) {
//                             return response(['success' => false, 'message' => "To qualify for this promo you need to make minimum transaction of N" . number_format($checkPromo->min_amount * $count)]);
//                         }
//                     }
//                 }
//         }


//         $network_name = $dataPlan->networks->name;
//         $phone  = $request->phone_number;
//         $validateNumber = substr($phone, 0, 4);

//         // if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || $_SERVER['HTTP_ISMART'] != "app") {
//         // if ($request->ported_number != "on") {

//         //     if ($network_name == "MTN") {
//         //         if (strpos("0702 0703 0713 0704 0706 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validateNumber) == FALSE || strlen($phone) != 11) {
//         //             return response(['success' => false, 'message' => "This number $phone is not an $network_name Number"]);
//         //         }
//         //     } else if ($network_name == "GLO") {
//         //         if (strpos("0805 0705 0905 0807 0907 0707 0817 0917 0717 0715 0815 0915 0811 0711 0911 ", $validateNumber) == FALSE || strlen($phone) != 11) {
//         //             return response(['success' => false, 'message' => "This number $phone is not a $network_name Number"]);
//         //         }
//         //     } else if ($network_name == "AIRTEL") {
//         //         if (strpos("0904 0802 0902 0702 0808 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validateNumber) == FALSE || strlen($phone) != 11) {
//         //             return response(['success' => false, 'message' => "This number $phone is not an $network_name Number"]);
//         //         }
//         //     } else if ($network_name == "9MOBILE") {
//         //         if (strpos("0809 0909 0709 0819 0919 0719 0817 0917 0717 0718 0918 0818 0808 0708 0908 ", $validateNumber) == FALSE || strlen($phone) != 11) {
//         //             return response(['success' => false, 'message' => "This number $phone is not a $network_name Number"]);
//         //         }
//         //     }
//         // }} 



//         if (  $dataPlan->status == "0") {
//             return response(['success' => false, 'message' => "Plan Is Currently not Available try again later"]);
//         }

//         if (($dataPlan->dataType->apiConnection == null ||  $dataPlan->dataType->apiConnection->status == "0") && $dataPlan->dataType->order_delivery == "1") {
//             return response(['success' => false, 'message' => "Plan Connection Currently down contact admin", 'data'=>$dataPlan->dataType]);
//         }
        
        
//         //  if (($item->airtimeType->apiConnection == null || $item->airtimeType->apiConnection->status == "0") && $item->airtimeType->order_delivery == "1") {
//         //     return response(['success' => false, 'message' => "Airtime Connection Currently down contact admin"]);
//         // }


//         if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api") {

//         // $userTransaction = $user->transaction()->latest()->first();
//         // if ($userTransaction) {
//         //     if ($userTransaction->created_at > Carbon::now()->subSeconds(20)  &&  $userTransaction->service ==  $dataPlan->networks->name) {
//         //         $tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
//         //         return response(['success' => false, 'message' => "Probably Duplicate Transaction $tt try again later",]);
//         //     }
//         // }

//     }

//         $TranxMessage = null;

//         //check user Wallet
//         $checkWallet =  checkUserWallet($user, $amount, $select_wallet_id);
//         if (!$checkWallet) {
//             return response(['success' => false, 'message' => "Insufficient Funds"]);
//         }

//         $wallet = $user->balance;
//         // $balance = $wallet - $amount;

//       $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
//       $init_balance = $balanceAction["init_balance"];
//       $final_balance = $balanceAction["final_balance"];

//         if ($balanceAction["success"]) {
//             $description = $dataPlan->networks->name . " " . $dataPlan->dataType->name . " " . $dataPlan->name . " to $request->phone_number";
//             $Trx = strtoupper(Str::random(16));
//             $ul['user_level_id'] = $user->user_level_id;
//             $ul['service'] = $dataPlan->networks->name;
//             $ul['api_connection_id'] = $dataPlan->dataType->apiConnection->id;
//             $ul['service_type_id'] = $request->dataType_id;
//             $ul['service_plan_id'] = $request->plan_id;
//             $ul['wallet_id'] = $select_wallet_id;
//             $ul['service_type'] = "Data Topup";
//             $ul['plan_size'] = $dataPlan->size;
//             $ul['real_price'] = $dataPlan->real_price;
//             $ul['charge'] = $amount - $dataPlan->real_price;
//             $ul['description'] = $description;
//             $ul['amount'] = $amount;
//             $ul['phone_number'] = $request->phone_number;
//             $ul['type'] = "0";
//             $ul['initial_balance'] = $init_balance;
//             $ul['final_balance'] = $final_balance;
//             $ul['reference'] = $Trx;
//             $ul['created_at'] = $date;
//             $ul['status'] = $finalStatus;
//             $tansaction =    $user->transaction()->create($ul);
//         } else {
//             return response(['success' => false, 'message' => "Wallet balance error"]);
//         }
//         $sucess = false;



//         //if delivery is manual
//         if ($dataPlan->dataType->order_delivery == "0") {
//             if ($tansaction) {
//                 $message = "{$user->profile->username} wants to buy data on " . $_SERVER['SERVER_NAME'] . "<br/> {$dataPlan->networks->name}  {$dataPlan->dataType->name}  $dataPlan->name  to $request->phone_number <br/>Reference Number: $Trx";
//                 try {
//                     $this->sendMail(getSiteSettings("email"), "Admin", 'Data Topup', $message);
//                 } catch (\Throwable $th) {
//                     //throw $th;
//                 }
//                 $TranxMessage = "Order will be delivered shortly thank you";
//                 $sucess = true;
//             }
//         } else {
//             $finalStatus = "success";
//             try {
//                 $json = null;
//                 $apiSuccessField = $dataPlan->dataType->apiConnection->success_field;
//                 $apiSuccessResponse = $dataPlan->dataType->apiConnection->success_response;
//                 $endpoint = $dataPlan->dataType->apiConnection->url;

//                 //header
//                 $Myheader = getHeaderApiConnection($dataPlan->dataType->apiConnection);
//                 $header = $Myheader["header"];
//                 $token = $Myheader["token"];

//                 if ($dataPlan->dataType->apiConnection->method == "0") {
//                     //api Connection GET method
//                     $endpoint = str_replace("{{planId}}", $dataPlan->id, $endpoint);
//                     $endpoint = str_replace("{{planCode}}", $dataPlan->code, $endpoint);
//                     $endpoint = str_replace("{{planExtraCode}}", $dataPlan->extra, $endpoint);
//                     $endpoint = str_replace("{{planSize}}", $dataPlan->size, $endpoint);
//                     $endpoint = str_replace("{{networkId}}", $dataPlan->networks->id, $endpoint);
//                     $endpoint = str_replace("{{networkCode}}", $dataPlan->networks->code, $endpoint);
//                     $endpoint = str_replace("{{networkName}}", $dataPlan->networks->name, $endpoint);
//                     $endpoint = str_replace("{{networkOtherName}}", $dataPlan->networks->other_name, $endpoint);
//                     $endpoint = str_replace("{{dataTypeId}}", $dataPlan->dataType->id, $endpoint);
//                     $endpoint = str_replace("{{dataTypeName}}", $dataPlan->dataType->name, $endpoint);
//                     $endpoint = str_replace("{{phoneNumber}}", $request->phone_number, $endpoint);
//                     $endpoint = str_replace("{{amount}}", $request->amount, $endpoint);
//                     $endpoint = str_replace("{{token}}", $token, $endpoint);
//                     $endpoint = str_replace("{{referenceNumber}}", $Trx, $endpoint);
//                     $endpoint = str_replace("{{current_date}}", date("Ymdhis"), $endpoint);
//                     $json = getHttp($endpoint, $header);
//                 } else {
//                     //APi Connection POST Method
//                     //payload
//                     $apiPayLoad = json_decode($dataPlan->dataType->apiConnection->payload, true);
//                     $payload = array();
//                     if ($apiPayLoad == null ||  !is_array($apiPayLoad)) {
//                         return response(['success' => false, 'message' => "Service Connection playload not active contact admin", "data"=>$dataPlan->dataType]);
//                     } else {
//                         foreach ($apiPayLoad as $key => $value) {
//                             $value = str_replace("{{planId}}", $dataPlan->id, $value);
//                             $value = str_replace("{{planCode}}", $dataPlan->code, $value);
//                             $value = str_replace("{{planExtraCode}}", $dataPlan->extra, $value);
//                             $value = str_replace("{{planSize}}", $dataPlan->size, $value);
//                             $value = str_replace("{{networkId}}", $dataPlan->networks->id, $value);
//                             $value = str_replace("{{networkCode}}", $dataPlan->networks->code, $value);
//                             $value = str_replace("{{networkName}}", $dataPlan->networks->name, $value);
//                             $value = str_replace("{{networkOtherName}}", $dataPlan->networks->other_name, $value);
//                             $value = str_replace("{{dataTypeId}}", $dataPlan->dataType->id, $value);
//                             $value = str_replace("{{dataTypeName}}", $dataPlan->dataType->name, $value);
//                             $value = str_replace("{{phoneNumber}}", $request->phone_number, $value);
//                             $value = str_replace("{{amount}}", $request->amount, $value);
//                             $value = str_replace("{{token}}", $token, $value);
//                             $value = str_replace("{{referenceNumber}}", $Trx, $value);
//                             $value = str_replace("{{current_date}}", date("Ymdhis"), $value);
//                             $payload[$key] = $value;
//                         }
//                     }

//                     if ($dataPlan->dataType->apiConnection->method == "1") {
//                         $json = postHttp($payload, $endpoint, $header);
//                     } else {
//                         $json = postHttpParameter($endpoint, $payload, $header);
//                     }

//                   // return response(['success' => false, 'message' => "here is the result ", $json, "header" => $header]);
//                 }
//                 if ($json != null) {
//                     $reponseVal = $json;
//                     foreach (explode('->', $apiSuccessField) as $itemS) {
//                         $reponseVal = $reponseVal->$itemS;
//                     }
//                     if ($reponseVal == $apiSuccessResponse) {
//                         $sucess = true;
//                     } else {
//                         $sucess = false;
//                     }
//                 }
//             } catch (\Throwable $th) {
//                 //throw $th;
//             }
//         } //end of automatic delivery

//         //check if success and Update Record
//         if ($sucess) {
//             $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus, 'message' => $TranxMessage]);
//             if ($query) {

//                 if ($dataPlan->promo_id != null) {
//                     $proT['promo_id'] = $dataPlan->promo_id;
//                     $proT['reference'] = $Trx;
//                     $promoTransaction =  $user->promoTransaction()->create($proT);
//                 }


//                 $description = $dataPlan->networks->name . " " . $dataPlan->dataType->name . " " . $dataPlan->name . " to $request->phone_number Reference ID Is : #$Trx";

//                 if (getSiteSettings("email_notify") == "Yes") {

//                     try {
//                         $this->sendMail($user->email, $user->profile->first_name, 'Data Topup', $description);
//                     } catch (\Throwable $th) {
//                         //throw $th;
//                     }
//                 }

//                 (new FirebaseUserUpdate)->addUserNotification($user,
//                 [
//                     "message" => $description,
//                     "type" => "transaction",
//                     "read" => 0,
//                     "data" => [
//                         "service" => 'Data Topup',
//                         "id" => $tansaction->id,
//                         "reference_id" => $Trx,
//                         "status" => $finalStatus,
//                         'date' => $date
//                     ]
//                 ]
//                 );


//                 if (getSiteSettings("phone_notify") == "Yes") {
//                     try {
//                         // $this->sendSms($user->phone_number, $description);
//                         sendTransactionSms($user, $description, 1);
//                     } catch (\Throwable $th) {
//                         //throw $th;
//                     }
//                 }
//                 //update firebase
//                 (new FirebaseUserUpdate)->updateUser($user);
//                 //check referral commission
//               iSmartBonus::referralCommission($user, $tansaction);
//                 return response()->json([
//                     'success' => true,
//                     'code' => 200,
//                     'status' => 'successful',
//                     'data' => [
//                         'service' => "Data Topup",
//                         'amount' => $amount,
//                         'plan' => $dataPlan->name,
//                         'phone_number' => $request->phone_number,
//                         'date' => $date,
//                         'message' => $TranxMessage ?? $description,
//                         'reference' => $Trx,
//                         'network' => $dataPlan->networks->name,
//                         'wallet_balance' => $final_balance,
//                     ],
//                     'user' => $user
//                 ]);
//             }
//         } else {
//             //update firebase
//             (new FirebaseUserUpdate)->updateUser($user);
//             if($dataPlan->dataType->apiConnection->auto_refund != "1" || $json == null){
//                 return response()->json([
//                     'success' => true,
//                     'status' => 'Pending',
//                     'message' => 'Transaction Processing',
//                     'data' => [
//                         'reference' => $Trx,
//                         'date' => $date
//                     ],
//                 ]);
//             }


//             $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful", 'message' => null]);
//             if ($query) {
//                 //refund user
//                 $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
//                 $init_balance = $balanceAction["init_balance"];
//                 $final_balance = $balanceAction["final_balance"];
//                 (new FirebaseUserUpdate)->updateUser($user);
//                 if ($balanceAction["success"]) {
//                     $description = $dataPlan->networks->name . " " . $dataPlan->dataType->name . " " . $dataPlan->name . " to $request->phone_number Refund";
//                     $trx = strtoupper(Str::random(16));
//                     $ul['user_level_id'] = $user->user_level_id;
//                     $ul['service'] = $dataPlan->networks->name;
//                     $ul['api_connection_id'] = $dataPlan->dataType->apiConnection->id;
//                     $ul['service_type_id'] = $request->dataType_id;
//                     $ul['service_plan_id'] = $request->plan_id;
//                     $ul['wallet_id'] = $select_wallet_id;
//                     $ul['service_type'] = "Data Topup";
//                     $ul['description'] = $description;
//                     $ul['amount'] = $amount;
//                     $ul['phone_number'] = $request->phone_number;
//                     $ul['type'] = "1";
//                     $ul['initial_balance'] = $init_balance;
//                     $ul['final_balance'] = $final_balance;
//                     $ul['reference'] = $trx;
//                     $ul['reference_ref'] = $Trx;
//                     $ul['status'] = "refund";
//                     $ul['created_at'] = $date;
//                     $tansaction =  $user->transaction()->create($ul);

//                     (new FirebaseUserUpdate)->addUserNotification($user,
//                 [
//                     "message" => $description,
//                     "type" => "transaction",
//                     "read" => 0,
//                     "data" => [
//                         "service" => 'Data Topup',
//                         "id" => $tansaction->id,
//                         "reference_id" => $Trx,
//                         "status" => "refund",
//                         'date' => $date
//                     ]
//                 ]
//                 );
//                 }
//             }
//             return response()->json([
//                 'success' => false,
//                 'status' => 'unsuccessful',
//                 'message' => 'Transaction unsuccessful',
//                 'data' => [
//                     'reference' => $Trx,
//                     'date' => $date
//                 ],
//             ]);
//         }
//     }
}
