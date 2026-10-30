<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Fintech\WithdrawalLog;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Telecom\VirtualAccount;
use App\TraitsFolder\MailTrait;
use App\Models\DepositFee;

class SafeHavenWebhookController extends Controller
{
    use MailTrait;
    
    public function virtualAccountTransferHook(Request $request)
    {
        try {
            Log::info('SafeHaven webhook received');
    
            $payload = $request->all();
            
            // SafeHaven can deliver the session id either nested in `data` or
            // at the top level depending on the webhook event version.
            $sessionId = $request->input('data.sessionId')
                ?? $request->input('sessionId');
            if (!is_string($sessionId) || trim($sessionId) === '') {
                Log::channel('safehaven')->warning('Webhook missing session id', [
                    'payload' => $payload,
                ]);
                return response()->json(['status' => 'failed', 'message' => 'Missing sessionId'], 422);
            }
            
            $verification = verifySafeHavenTransfer($sessionId);

            if (!$verification['verified']) {
                Log::channel('safehaven')->error('Transfer verification failed', [
                    'sessionId' => $sessionId,
                    'verified' => false
                ]);
                return response()->json([
                    'status' => 'failed',
                    'message' => $verification['message'],
                    'data' => $verification['data']
                ], 400);
            }
    
            $requiredFields = [
                'type',
                'data.paymentReference',
                'data.creditAccountNumber',
                'data.amount',
                'data.status',
                'data.responseCode'
            ];
    
            // foreach ($requiredFields as $field) {
            //     if (!Arr::has($payload, $field)) {
            //         $errorMsg = "SafeHaven Webhook Error: Missing required field - $field";
            //         Log::error($errorMsg, ['payload' => $payload]);
            //         return response($errorMsg, 400);
            //     }
            // }
    
            $eventType = strtolower((string) ($payload['type'] ?? ''));
            $eventStatus = strtolower((string) ($payload['data']['status'] ?? ''));
            $responseCode = (string) ($payload['data']['responseCode'] ?? '');
            if (
                $eventType !== 'transfer' ||
                !in_array($eventStatus, ['completed', 'successful', 'success'], true) ||
                $responseCode !== '00'
            ) {
                $errorMsg = "SafeHaven Webhook Error: Transaction not successful";
                Log::warning($errorMsg, [
                    'type' => $payload['type'] ?? null,
                    'status' => $payload['data']['status'] ?? null,
                    'responseCode' => $payload['data']['responseCode'] ?? null
                ]);
                return response($errorMsg, 400);
            }
    
            $paymentReference = (string) ($payload['data']['paymentReference'] ?? '');
            $accountNumber = trim((string) ($payload['data']['creditAccountNumber'] ?? ''));
            $amountPaid = (float) ($payload['data']['amount'] ?? 0);
            if ($paymentReference === '' || $accountNumber === '' || $amountPaid <= 0) {
                return response()->json(['status' => 'failed', 'message' => 'Invalid transfer payload'], 422);
            }
            $paidOn = $payload['data']['approvedAt'] ?? now();
    
            $bankAccount = VirtualAccount::where('account_number', $accountNumber)->first();
            if (!$bankAccount) {
                $errorMsg = "SafeHaven Webhook Error: Bank account not found - $accountNumber";
                Log::error($errorMsg, ['accountNumber' => $accountNumber]);
                return response($errorMsg, 404);
            }
    
            $user = $bankAccount->user;
    
            if (Payment::where('reference', $paymentReference)->exists()) {
                $errorMsg = "SafeHaven Webhook Error: Duplicate transaction - $paymentReference";
                Log::warning($errorMsg, ['paymentReference' => $paymentReference]);
                return response($errorMsg, 400);
            }
    
            $gateway = PaymentGateway::where('name', 'like', '%SafeHaven%')->first();
    
            // if ($gateway && $gateway->rate == "FLAT") {
            //     $rate = $gateway->charge / 100 * $amountPaid;
            //     $amount_to_credit = $amountPaid - $rate;
            // } elseif ($gateway) {
            //     $amount_to_credit = $amountPaid - $gateway->charge;
            // } else {
            //     $amount_to_credit = $amountPaid;
            // }
            
            // $feeRecord = DepositFee::where('currency', 'NGN')
            // ->where('minimum_amount', '<=', $amountPaid)
            // ->where('maximum_amount', '>=', $amountPaid)
            // ->first();
            
            // Handle deposit fee with unlimited maximum amount consideration
            $feeRecord = DepositFee::where('currency', 'NGN')
                ->where('minimum_amount', '<=', $amountPaid)
                ->where(function ($query) use ($amountPaid) {
                    $query->where('maximum_amount', '>=', $amountPaid)
                          ->orWhere('maximum_amount', 'unlimited')
                          ->orWhereNull('maximum_amount');
                })
                ->orderBy('minimum_amount', 'desc')
                ->first();
    
            // If no specific range found, look for the unlimited rule as fallback
            if (!$feeRecord) {
                $feeRecord = DepositFee::where('currency', 'NGN')
                    ->where(function ($query) {
                        $query->where('maximum_amount', 'unlimited')
                              ->orWhereNull('maximum_amount');
                    })
                    ->orderBy('minimum_amount', 'desc')
                    ->first();
            }
            
            $fee = 0;
            if($feeRecord) {
                if (($feeRecord->charge_type ?? 'percentage') === 'fixed') {
                    // Flat fee in NGN
                    $fee = (float) $feeRecord->fee;
                } else {
                    // Percentage-based fee
                    $fee = ($feeRecord->fee / 100) * $amountPaid;
                }
                // Optional cap (0 = no cap)
                if (isset($feeRecord->cap) && $feeRecord->cap > 0 && $fee > $feeRecord->cap) {
                    $fee = $feeRecord->cap;
                }
                $amount_to_credit = $amountPaid - $fee;
            } else {
                $amount_to_credit = $amountPaid;
            }
    
            $payment = $user->payment()->create([
                'payment_gateway_id' => $gateway->id ?? null,
                'type' => 'Virtual Account',
                'reference' => $paymentReference,
                'amount' => $amountPaid,
                'status' => 'success',
                'date' => $paidOn
            ]);
            
            
    
            $initialBalance = $user->balance;
            // RACE-SAFE credit: row-locked, atomic. $user is kept in sync by the helper.
            $creditAction = userBalanceAction($user, $amount_to_credit, "1", "+");
            if (empty($creditAction["success"])) {
                \Log::error("SafeHaven funding credit failed", ["user_id" => $user->id, "reason" => $creditAction["reason"] ?? null]);
                return response(["success" => false, "message" => "Could not credit wallet, please contact support."]);
            }
    
            $description = "Transfer from " . strtoupper($payload['data']['debitAccountName']);
            $trx = strtoupper(Str::random(20));
            $ul = [
                'wallet_id' => '1',
                'service' => "System",
                'service_type' => "Wallet Funding",
                'description' => $description,
                'amount' => $amountPaid,
                'real_price' => $amountPaid - $fee,
                'charge' => $fee,
                'phone_number' => $user->phone_number,
                'type' => '1',
                'initial_balance' => $initialBalance,
                'final_balance' => $initialBalance + $amount_to_credit,
                'reference' => $trx,
                'paymentReference' => $paymentReference,
                'status' => "success",
                'sender_bank_name' => $payload['data']['destinationInstitutionCode'],
                'sender_account_name' => $payload['data']['debitAccountName'],
                'sender_account_number' => $payload['data']['debitAccountNumber'],
                'session_id' => $payload['data']['sessionId'],
            ];
            $transaction = $user->transaction()->create($ul);
            
    
            // First Deposit Bonus check
            $checkIfNotFirstPayment = Payment::where('user_id', $user->id)->exists();
    
            if (!$checkIfNotFirstPayment && getSiteSettings("enable_first_deposit_bonus") == "Yes") {
                $bonusPercentage = getSiteSettings("first_deposit_bonus");
                $bonus = $bonusPercentage / 100 * $amountPaid;
                $description = "Bonus of ₦$bonus on your first deposit of ₦$amountPaid";
                iSmartBonus::addUpBonus($user, $bonus, $paymentReference, $description);
            }
            // Every Deposit Bonus
            elseif (getSiteSettings("enable_deposit_bonus") == "Yes" && getSiteSettings("deposit_bonus") > 0) {
                $bonusPercentage = getSiteSettings("deposit_bonus");
                $bonus = $bonusPercentage / 100 * $amountPaid;
                $description = "Bonus of ₦$bonus on your deposit of ₦$amountPaid";
                iSmartBonus::addUpBonus($user, $bonus, $paymentReference, $description);
            }
    
            // Send notifications
            try {
                if (getSiteSettings("email_notify") == "Yes") {
                    $maskedAccount = substr($accountNumber, 0, 1) . 'XX..' . substr($accountNumber, -2);
                    $formattedDate = now()->format('d-M-Y h:i A');
                    $formattedAmount = number_format($amountPaid, 2);
                    $formattedBalance = number_format($user->balance, 2);
                    $description = $payload['data']['narration'] ?? 'Wallet Funding';
                    $accountName = $payload['data']['debitAccountName'];  
                
                    $message = "
                        <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                        <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                                <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                                <td style='padding:8px; border:1px solid #ddd;'>CREDIT ALERT</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                                <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                                <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Account Number</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$maskedAccount}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$payload['data']['sessionId']}</td>
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
                
                    // Send the email
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name,
                        "Dupay Credit Transaction Notification",
                        $message
                    );
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $maskedAccount = substr($accountNumber, 0, 1) . 'XX..' . substr($accountNumber, -2);
                    $formattedDate = now()->format('d-m-Y H:i');
                    $formattedBalance = 'NGN ' . number_format($user->balance, 2);
                    $amt = 'NGN ' . number_format($amountPaid, 2);
                
                    $description = $payload['data']['narration'] ?? 'Wallet Funding';
                
                    $smsMessage = "Txn: Credit\n" .
                                  "Ac: {$maskedAccount}\n" .
                                  "Amt: {$amt}\n" .
                                  "Des: {$description}\n" .
                                  "ACCOUNT/Tran\n" .
                                  "Date: {$formattedDate}\n" .
                                  "Bal: {$formattedBalance}";
                
                    sendTransactionSms($user, $smsMessage, 1);
                }
            } catch (\Throwable $th) {
                Log::error("SafeHaven Webhook: Notification failed", [
                    'error' => $th->getMessage()
                ]);
            }
    
            return response("Wallet funded successfully", 200);
    
        } catch (\Throwable $th) {
            Log::error("SafeHaven Webhook: Unexpected error", [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response("An error occurred while processing the webhook. Error: " . $th->getMessage() , 500);
        }
    }

}
