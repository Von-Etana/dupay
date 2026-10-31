<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use iSmartFlutterWave;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use App\Models\VatSetting;
use App\Models\StampDutySetting;

class TransferController extends Controller
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
     *          Money Request
     *
     *
     */

    // public function requestFund(Request $request)
    // {
    //     $date = Carbon::now();
    //     $user = Auth::user();
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Validation
    //     |--------------------------------------------------------------------------
    //     */
    //     $validator = Validator::make($request->all(), [
    //         'wallet_id'       => 'nullable',
    //         'receiver_id'     => 'required|integer',
    //         'amount'          => 'required|numeric|min:1',
    //         'comment'         => 'nullable|string',
    //         'transaction_pin' => 'nullable'
    //     ]);
    
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => implode(' ', $validator->errors()->all())
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Global Checks
    //     |--------------------------------------------------------------------------
    //     */
    //     if (getSiteSettings("enable_transfer") !== "Yes") {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Sorry Fund Request is disabled at the moment"
    //         ]);
    //     }
    
    //     if ($request->amount < getSiteSettings("min_transfer")) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Minimum Request amount is " . getSiteSettings("min_transfer")
    //         ]);
    //     }
    
    //     if ($request->amount > getSiteSettings("max_transfer")) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Maximum Request amount is " . getSiteSettings("max_transfer")
    //         ]);
    //     }
    
    //     $walletId = $request->wallet_id ?? 1;
    
    //     if (!checkWalletEnable("TransferFund", $walletId)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Selected wallet not allowed for this transaction"
    //         ]);
    //     }
    
    //     if ($user->id == $request->receiver_id) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "You can’t request fund from yourself"
    //         ]);
    //     }
    
    //     $receiver = User::find($request->receiver_id);
    //     if (!$receiver) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Receiver not found"
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Transaction PIN Check
    //     |--------------------------------------------------------------------------
    //     */
    //     if (
    //         !isset($_SERVER['HTTP_ISMART']) ||
    //         $_SERVER['HTTP_ISMART'] !== "api" ||
    //         request("fingerPrint") != true
    //     ) {
    //         if (!$request->transaction_pin) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Please enter transaction pin"
    //             ]);
    //         }
    
    //         if (!$user->transaction_pin) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Transaction pin not set"
    //             ]);
    //         }
    
    //         if (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Incorrect transaction pin"
    //             ]);
    //         }
    //     }
        
    //     if($user->blocked_status == true){
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Your account has been temporarily blocked due to unusual activity. Please contact support to restore access."
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Charges Calculation
    //     |--------------------------------------------------------------------------
    //     */
    //     $amount = $request->amount;
        
    //     $transferCharge = 0;
    //     if (getSiteSettings("enable_transfer_charge") === "Yes") {
    //         $transferCharge = calCharge($amount, "1");
            
    //         if ($user->level->transfer_charge > 0) {
    //             $transferCharge = ($user->level->transfer_charge / 100) * $amount;
    //         }
            
    //         if ($transferCharge < 1 && $user->level->transfer_charge < 1) {
    //             $transferCharge = getSiteSettings("default_transfer_charge");
    //         }
    //     }
        
    //     // Stamp Duty - FIXED VERSION
    //     $stampDuty = 0;
    //     $stampDutySetting = StampDutySetting::first();
        
    //     // Check if setting exists AND amount meets the threshold
    //     if ($stampDutySetting && $amount >= $stampDutySetting->start) {
    //         $stampDuty = $stampDutySetting->charge;
            
    //         // If charge is a percentage, calculate it
    //         if ($stampDutySetting->charge_type === 'percentage' || $stampDutySetting->charge < 1) {
    //             $stampDuty = ($stampDutySetting->charge / 100) * $amount;
    //         }
    //     }
        
    //     // VAT
    //     $vatPercent = getSiteSettings('vat_percent');
    //     $vat = ($vatPercent / 100) * $transferCharge;
        
    //     $totalCharge = $transferCharge + $vat + $stampDuty;
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Balance Check (CHARGES ONLY)
    //     |--------------------------------------------------------------------------
    //     */
    //     if ($user->balance < $totalCharge) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Insufficient balance to cover charges (₦" . number_format($totalCharge, 2) . ")"
    //         ]);
    //     }
    
    //     DB::beginTransaction();
    
    //     try {
    
    //         $trx = strtoupper(Str::random(16));
    //         $trxs = strtoupper(Str::random(16));
    
    //         /*
    //         |--------------------------------------------------------------------------
    //         | Create Fund Request
    //         |--------------------------------------------------------------------------
    //         */
    //         $user->requestFund()->create([
    //             'from_user_id' => $receiver->id,
    //             'amount'       => $amount,
    //             'charge'       => $totalCharge,
    //             'reference'    => $trx,
    //             'comment'      => $request->comment,
    //             'status'       => '0'
    //         ]);
    
    //         /*
    //         |--------------------------------------------------------------------------
    //         | Fund Request Transaction
    //         |--------------------------------------------------------------------------
    //         */
    //         $user->transaction()->create([
    //             'user_level_id'   => $user->user_level_id,
    //             'wallet_id'       => $walletId,
    //             'service'         => 'System',
    //             'service_type'    => 'Fund Request',
    //             'description'     => "Fund request to {$receiver->profile->username} | Ref: #$trx",
    //             'amount'          => $amount,
    //             'type'            => '0',
    //             'initial_balance' => $user->balance,
    //             'final_balance'   => $user->balance,
    //             'reference'       => $trx,
    //             'status'          => 'pending',
    //         ]);
            
    //         /*
    //         |--------------------------------------------------------------------------
    //         | Debit Stamp Duty NOW
    //         |--------------------------------------------------------------------------
    //         */
    //         if ($stampDuty > 0) {
    
    //             $initialBalance = $user->balance;
    //             $user->balance -= $stampDuty;
    //             $user->save();
    
    //             $user->transaction()->create([
    //                 'user_level_id'   => $user->user_level_id,
    //                 'wallet_id'       => $walletId,
    //                 'service'         => 'System',
    //                 'service_type'    => 'Stamp Duty',
    //                 'description'     => "Stamp duty charge | Ref: #$trx",
    //                 'amount'          => $stampDuty,
    //                 'type'            => '0',
    //                 'initial_balance' => $initialBalance,
    //                 'final_balance'   => $user->balance,
    //                 'reference'       => $trxs,
    //                 'status'          => 'success',
    //             ]);
    //         }
    
    //         DB::commit();
    
    //     } catch (\Throwable $e) {
    
    //         DB::rollBack();
    
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Transaction failed'
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Response
    //     |--------------------------------------------------------------------------
    //     */
    //     return response()->json([
    //         'success' => true,
    //         'status'  => 'successful',
    //         'message' => 'Fund Request Successful',
    //         'data'    => [
    //             'amount'         => $amount,
    //             'transfer_charge'=> $transferCharge,
    //             'vat'            => $vat,
    //             'stamp_duty'     => $stampDuty,
    //             'total_charge'   => $totalCharge,
    //             'reference'      => $trx,
    //             'date'           => $date,
    //             'wallet_balance' => $user->balance
    //         ]
    //     ]);
    // }


     /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *          P2P Transfer
     *
     *
     */
     
     
    public function transferBankTwo(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
    
        $validatedData = Validator::make($data, [
            'wallet_id' => 'nullable|integer',
            'bank' => 'required|string',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'comment' => 'nullable|string',
            'account_name' => 'required|string',
            'session_id' => 'required',
            'transaction_pin' => 'nullable|string',
        ]);
    
        if ($validatedData->fails()) {
            return response([
                'success' => false,
                'message' => implode(' ', $validatedData->errors()->all())
            ]);
        }
    
        $user = Auth::user();
        $walletId = $request->wallet_id ?? 1;
    
        $amountRaw = preg_replace('/[^\d.]/', '', $request->amount);
        $requestAmount = floatval($amountRaw);
    
        /*
        |--------------------------------------------------------------------------
        | TRANSACTION PIN CHECK
        |--------------------------------------------------------------------------
        */
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
                'message' => "Your account has been temporarily blocked. Please contact support."
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | SETTINGS CHECK
        |--------------------------------------------------------------------------
        */
        if (getSiteSettings("enable_withdrawal") !== "Yes") {
            return response(['success' => false, 'message' => "Withdrawals are currently disabled."]);
        }
    
        if ($requestAmount < getSiteSettings("min_withdrawal")) {
            return response(['success' => false, 'message' => "Minimum withdrawal is " . getSiteSettings("min_withdrawal")]);
        }
    
        if ($requestAmount > getSiteSettings("max_withdrawal")) {
            return response(['success' => false, 'message' => "Maximum withdrawal is " . getSiteSettings("max_withdrawal")]);
        }
    
        $limitCheck = checkDailyTransactionLimit($user, $requestAmount);
        if (!$limitCheck['success']) {
            return response(['success' => false, 'message' => $limitCheck['message']]);
        }
    
        if (!checkWalletEnable("WithdrawFund", $walletId)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed"]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */
        $charge = 0;
        $p_charge = 0;
    
        if (getSiteSettings("enable_withdrawal_charge") === "Yes") {
            $charge = getSiteSettings("default_withdrawal_charge");
        }
    
        if (getSiteSettings("enable_withdrawal_percent_charge") === "Yes") {
            $percent_charge = floatval(getSiteSettings("withdrawal_percent_charge"));
            $capped_charge = floatval(getSiteSettings("withdrawal_capped_amount"));
    
            $calculated = ($percent_charge / 100) * $requestAmount;
            $p_charge = $capped_charge > 0 ? min($calculated, $capped_charge) : $calculated;
        }
    
        /*
        |--------------------------------------------------------------------------
        | VAT
        |--------------------------------------------------------------------------
        */
        $vatAmount = 0;
        $vatPercent = getSiteSettings('vat_percent');
        if ($vatPercent > 0) {
            $vatAmount = ($vatPercent / 100) * ($charge + $p_charge);
        }
    
        /*
        |--------------------------------------------------------------------------
        | STAMP DUTY
        |--------------------------------------------------------------------------
        */
        $stampDuty = 0;
        $stampDutySetting = StampDutySetting::first();
        if ($stampDutySetting && $requestAmount >= $stampDutySetting->start) {
            $stampDuty = $stampDutySetting->charge;
        }
    
        $totalCharges = $charge + $p_charge + $vatAmount + $stampDuty;
        $totalDebit = $requestAmount + $totalCharges;
    
        if (!checkUserWallet($user, $totalDebit, $walletId)) {
            return response(['success' => false, 'message' => "Insufficient funds"]);
        }
    
        $balanceAction = userBalanceAction($user, $totalDebit, $walletId, "-");
        if (!$balanceAction['success']) {
            return response(['success' => false, 'message' => $balanceAction['message'] ?? "Wallet balance error"]);
        }
    
        $trx = strtoupper(Str::random(16));
        $trxs = strtoupper(Str::random(16));
        $description = "Transfer to " . strtoupper($request->account_name);
    
        /*
        |--------------------------------------------------------------------------
        | CREATE TRANSACTION
        |--------------------------------------------------------------------------
        */
        $transaction = $user->transaction()->create([
            'user_level_id' => $user->user_level_id,
            'wallet_id' => $walletId,
            'service' => 'System',
            'service_type' => 'Fund Withdrawal',
            'description' => $description,
            'amount' => $requestAmount,
            'real_price' => $requestAmount - ($charge + $p_charge),
            'charge' => $charge + $p_charge,
            'vat' => $vatAmount,
            'type' => 0,
            'initial_balance' => $balanceAction['init_balance'],
            'final_balance' => $balanceAction['final_balance'],
            'reference' => $trx,
            'status' => 'pending',
            'receiver_bank_name' => $request->bank_name,
            'receiver_account_name' => $request->account_name,
            'receiver_account_number' => $request->account_number,
        ]);
        
                /*
        |--------------------------------------------------------------------------
        | STAMP DUTY TRANSACTION (SEPARATE RECORD)
        |--------------------------------------------------------------------------
        */
        if ($stampDuty > 0) {
            $user->transaction()->create([
                'user_level_id' => $user->user_level_id,
                'wallet_id' => $walletId,
                'service' => 'System',
                'service_type' => 'Stamp Duty',
                'description' => "Stamp duty charge | Ref: #$trx",
                'amount' => $stampDuty,
                'type' => 0,
                'initial_balance' => $balanceAction['init_balance'],
                'final_balance' => $balanceAction['final_balance'],
                'reference' => $trxs,
                'status' => 'success',
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | AUTO WITHDRAWAL
        |--------------------------------------------------------------------------
        */
        $resDesc = "Processing your request";
        $finalStatus = "pending";
        $status = 0;
    
        if (getSiteSettings("enable_auto_withdrawal") === "Yes") {
    
            // $response = sudoBankTransfer([
            //     'debitAccountId' => '68514e0fcb653e20408c42d0',
            //     'beneficiaryBankCode' => $request->bank,
            //     'beneficiaryAccountNumber' => $request->account_number,
            //     'amount' => $requestAmount,
            //     'narration' => $request->comment,
            //     'paymentReference' => $trx,
            // ]);
            
            $response = safeHavenBankTransfer([
                'name_enquiry_reference'   => $request->session_id,
                'debit_account_number'     => '0118654890',
                'beneficiary_bank_code'    => $request->bank,
                'beneficiary_account_number'=> $request->account_number,
                'amount'                   => $requestAmount,
                'save_beneficiary'         => false,
                'narration'                => $request->comment,
                'payment_reference'        => $trx,
            ]);
    
            $resDesc = $response['message'];
            
            $sessionId = $trx;
		
    		if ($response['success'] && isset($response['data']->data->sessionId)) {
    			$sessionId = $response['data']->data->sessionId;
    		}
        
            if ($response['success']) {
                $status = 1;
                $finalStatus = 'success';
            } elseif (getSiteSettings("enable_auto_withdrawal_refund") === "Yes") {
                $finalStatus = 'unsuccessful';
                $status = 4;
            }
            
            if ($sessionId) {
                $transaction->update(['session_id' => $sessionId]);
            }
    
            $transaction->update(['status' => $finalStatus]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | WITHDRAWAL LOG
        |--------------------------------------------------------------------------
        */
        $withdrawal = $user->withdrawalLog()->create([
            "bank_code" => $request->bank,
            "bank_name" => $request->bank_name,
            "account_name" => $request->account_name,
            "account_number" => $request->account_number,
            "amount" => $requestAmount,
            "charge" => $totalCharges,
            "referrence" => $trx,
            "comment" => $request->comment,
            "status" => $status,
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL & SMS IF SUCCESS
        |--------------------------------------------------------------------------
        */
        if ($finalStatus === 'success') {
            
            

            $maskedAccount = substr($request->account_number ?? '1234567890', 0, 1) . "XX..XX" . substr($request->account_number ?? '1234567890', -2);
            $formattedDate = Carbon::now()->format('d-M-Y h:i A');
        
            $message = "
            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
            <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$request->account_name}</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                    <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($requestAmount, 2) . "</td>
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
                    <td style='padding:8px; border:1px solid #ddd;'>FUND WITHDRAWAL to {$request->bank_name}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$sessionId}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                </tr>
            </table>
            <br>
            <p>Thank you for using Dupay.</p>
            ";
        
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name,
                        'Dupay Debit Transaction Notification',
                        $message
                    );
                } catch (\Throwable $th) {
                    // Log or ignore
                }
            }
        
            if (getSiteSettings("phone_notify") === "Yes") {
                $vir = $user->virtualAccount->account_number;
                $maskedAccount = substr($vir, 0, 1) . "XX..XX" . substr($vir, -2);
                $formattedDate = Carbon::now()->format('d-m-Y H:i');
                $balance = number_format($balanceAction['final_balance'], 2);
                $amountFormatted = number_format($requestAmount, 2);
        
                $smsMessage = "Txn: Debit\n"
                    . "Ac:" . $maskedAccount . "\n"
                    . "Amt:NGN " . $amountFormatted . "\n"
                    . "Des:FUND WITHDRAWAL to {$request->bank_name}\n"
                    . "Date:" . $formattedDate . "\n"
                    . "Bal:NGN " . $balance;
        
                sendTransactionSms($user, $smsMessage, 1);
            }
            
            // The transfer has ALREADY completed at this point. The referral
            // commission is non-critical — if it throws, it must NOT 500 the
            // request (a 500 makes the client retry and re-send the money).
            try {
                referralCommission($user, $transaction, $requestAmount);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('referralCommission failed (transfer already succeeded): ' . $e->getMessage());
            }
        }
    
        (new FirebaseUserUpdate)->updateUser($user);
    
        return response()->json([
            'success' => $finalStatus === 'success',
            'status' => $finalStatus,
            'message' => 'Transaction unsuccessful: ' . $resDesc,
            'data' => [
                'service' => "Fund Withdrawal",
                'amount' => $requestAmount,
                'charge' => $charge + $p_charge,
                'reference' => $trx,
                'wallet_balance' => $balanceAction['final_balance'],
                'from' => "Wallet",
                'date' => $date,
                'to' => "{$request->account_number} {$request->account_name}",
            ],
        ]);
    }
    
    public function transferBank(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
    
        $validatedData = Validator::make($data, [
            'wallet_id' => 'nullable|integer',
            'bank' => 'required|string',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'comment' => 'nullable|string',
            'account_name' => 'required|string',
            'session_id' => 'required',
            'transaction_pin' => 'nullable|string',
        ]);
    
        if ($validatedData->fails()) {
            return response([
                'success' => false,
                'message' => implode(' ', $validatedData->errors()->all())
            ]);
        }
    
        $user = Auth::user();
        $walletId = $request->wallet_id ?? 1;
    
        $amountRaw = preg_replace('/[^\d.]/', '', $request->amount);
        $requestAmount = floatval($amountRaw);
    
        /*
        |--------------------------------------------------------------------------
        | TRANSACTION PIN CHECK
        |--------------------------------------------------------------------------
        */
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
                'message' => "Your account has been temporarily blocked. Please contact support."
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | SETTINGS CHECK
        |--------------------------------------------------------------------------
        */
        if (getSiteSettings("enable_withdrawal") !== "Yes") {
            return response(['success' => false, 'message' => "Withdrawals are currently disabled."]);
        }
    
        if ($requestAmount < getSiteSettings("min_withdrawal")) {
            return response(['success' => false, 'message' => "Minimum withdrawal is " . getSiteSettings("min_withdrawal")]);
        }
    
        if ($requestAmount > getSiteSettings("max_withdrawal")) {
            return response(['success' => false, 'message' => "Maximum withdrawal is " . getSiteSettings("max_withdrawal")]);
        }
    
        $limitCheck = checkDailyTransactionLimit($user, $requestAmount);
        if (!$limitCheck['success']) {
            return response(['success' => false, 'message' => $limitCheck['message']]);
        }
    
        if (!checkWalletEnable("WithdrawFund", $walletId)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed"]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */
        $charge = 0;
        $p_charge = 0;
    
        if (getSiteSettings("enable_withdrawal_charge") === "Yes") {
            $charge = getSiteSettings("default_withdrawal_charge");
        }
    
        if (getSiteSettings("enable_withdrawal_percent_charge") === "Yes") {
            $percent_charge = floatval(getSiteSettings("withdrawal_percent_charge"));
            $capped_charge = floatval(getSiteSettings("withdrawal_capped_amount"));
    
            $calculated = ($percent_charge / 100) * $requestAmount;
            $p_charge = $capped_charge > 0 ? min($calculated, $capped_charge) : $calculated;
        }
    
        /*
        |--------------------------------------------------------------------------
        | VAT
        |--------------------------------------------------------------------------
        */
        $vatAmount = 0;
        $vatPercent = getSiteSettings('vat_percent');
        if ($vatPercent > 0) {
            $vatAmount = ($vatPercent / 100) * ($charge + $p_charge);
        }
    
        /*
        |--------------------------------------------------------------------------
        | STAMP DUTY
        |--------------------------------------------------------------------------
        */
        $stampDuty = 0;
        $stampDutySetting = StampDutySetting::first();
        if ($stampDutySetting && $requestAmount >= $stampDutySetting->start) {
            $stampDuty = $stampDutySetting->charge;
        }
    
        $totalCharges = $charge + $p_charge + $vatAmount + $stampDuty;
        $totalDebit = $requestAmount + $totalCharges;
    
        if (!checkUserWallet($user, $totalDebit, $walletId)) {
            return response(['success' => false, 'message' => "Insufficient funds"]);
        }

        /*
        |--------------------------------------------------------------------------
        | IDEMPOTENCY GUARD (prevents duplicate / retried withdrawals)
        |--------------------------------------------------------------------------
        | session_id comes from the name-enquiry step and is unique per
        | withdrawal attempt. If the client retries the same request (e.g. on a
        | timeout or a 500), it re-sends the SAME session_id. Cache::add() is
        | atomic - it only succeeds if the key does not already exist - so the
        | first request claims the key and any duplicate is rejected BEFORE the
        | wallet is debited. This is what stops the money being sent 2-3 times.
        */
        $idempotencyKey = 'withdraw_lock_' . $user->id . '_' . $request->session_id;
        if (!\Illuminate\Support\Facades\Cache::add($idempotencyKey, true, now()->addMinutes(15))) {
            return response()->json([
                'success' => false,
                'message' => 'This withdrawal is already being processed. Please check your transaction history before trying again.',
            ], 409);
        }

        $balanceAction = userBalanceAction($user, $totalDebit, $walletId, "-");
        if (!$balanceAction['success']) {
            return response(['success' => false, 'message' => $balanceAction['message'] ?? "Wallet balance error"]);
        }
    
        $trx = strtoupper(Str::random(16));
        $trxs = strtoupper(Str::random(16));
        $description = "Transfer to " . strtoupper($request->account_name);
    
        /*
        |--------------------------------------------------------------------------
        | CREATE TRANSACTION
        |--------------------------------------------------------------------------
        */
        $transaction = $user->transaction()->create([
            'user_level_id' => $user->user_level_id,
            'wallet_id' => $walletId,
            'service' => 'System',
            'service_type' => 'Fund Withdrawal',
            'description' => $description,
            'amount' => $requestAmount,
            'real_price' => $requestAmount - ($charge + $p_charge),
            'charge' => $charge + $p_charge,
            'vat' => $vatAmount,
            'type' => 0,
            'initial_balance' => $balanceAction['init_balance'],
            'final_balance' => $balanceAction['final_balance'],
            'reference' => $trx,
            'status' => 'pending',
            'receiver_bank_name' => $request->bank_name,
            'receiver_account_name' => $request->account_name,
            'receiver_account_number' => $request->account_number,
        ]);
        
                /*
        |--------------------------------------------------------------------------
        | STAMP DUTY TRANSACTION (SEPARATE RECORD)
        |--------------------------------------------------------------------------
        */
        if ($stampDuty > 0) {
            $user->transaction()->create([
                'user_level_id' => $user->user_level_id,
                'wallet_id' => $walletId,
                'service' => 'System',
                'service_type' => 'Stamp Duty',
                'description' => "Stamp duty charge | Ref: #$trx",
                'amount' => $stampDuty,
                'type' => 0,
                'initial_balance' => $balanceAction['init_balance'],
                'final_balance' => $balanceAction['final_balance'],
                'reference' => $trxs,
                'status' => 'success',
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | AUTO WITHDRAWAL
        |--------------------------------------------------------------------------
        */
        $resDesc = "Processing your request";
        $finalStatus = "pending";
        $status = 0;
    
        if (getSiteSettings("enable_auto_withdrawal") === "Yes") {
    
            // $response = sudoBankTransfer([
            //     'debitAccountId' => '68514e0fcb653e20408c42d0',
            //     'beneficiaryBankCode' => $request->bank,
            //     'beneficiaryAccountNumber' => $request->account_number,
            //     'amount' => $requestAmount,
            //     'narration' => $request->comment,
            //     'paymentReference' => $trx,
            // ]);
            
            $response = safeHavenBankTransfer([
                'name_enquiry_reference'   => $request->session_id,
                'debit_account_number'     => '0118654890',
                'beneficiary_bank_code'    => $request->bank,
                'beneficiary_account_number'=> $request->account_number,
                'amount'                   => $requestAmount,
                'save_beneficiary'         => false,
                'narration'                => $request->comment,
                'payment_reference'        => $trx,
            ]);
    
            $resDesc = $response['message'];
            
            $sessionId = $trx;
		
    		if ($response['success'] && isset($response['data']['sessionId'])) {
                $sessionId = $response['data']['sessionId'];
            }
        
            if ($response['success']) {
                $status = 1;
                $finalStatus = 'success';
            } elseif (getSiteSettings("enable_auto_withdrawal_refund") === "Yes") {
                $finalStatus = 'unsuccessful';
                $status = 4;
            }
            
            if ($sessionId) {
                $transaction->update(['session_id' => $sessionId]);
            }
    
            $transaction->update(['status' => $finalStatus]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | WITHDRAWAL LOG
        |--------------------------------------------------------------------------
        */
        $withdrawal = $user->withdrawalLog()->create([
            "bank_code" => $request->bank,
            "bank_name" => $request->bank_name,
            "account_name" => $request->account_name,
            "account_number" => $request->account_number,
            "amount" => $requestAmount,
            "charge" => $totalCharges,
            "referrence" => $trx,
            "comment" => $request->comment,
            "status" => $status,
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL & SMS IF SUCCESS
        |--------------------------------------------------------------------------
        */
        if ($finalStatus === 'success') {
            
            

            $maskedAccount = substr($request->account_number ?? '1234567890', 0, 1) . "XX..XX" . substr($request->account_number ?? '1234567890', -2);
            $formattedDate = Carbon::now()->format('d-M-Y h:i A');
        
            $message = "
            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
            <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$request->account_name}</strong></td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                    <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
                    <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($requestAmount, 2) . "</td>
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
                    <td style='padding:8px; border:1px solid #ddd;'>FUND WITHDRAWAL to {$request->bank_name}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$sessionId}</td>
                </tr>
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                </tr>
            </table>
            <br>
            <p>Thank you for using Dupay.</p>
            ";
        
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name,
                        'Dupay Debit Transaction Notification',
                        $message
                    );
                } catch (\Throwable $th) {
                    // Log or ignore
                }
            }
        
            if (getSiteSettings("phone_notify") === "Yes") {
                $vir = $user->virtualAccount->account_number;
                $maskedAccount = substr($vir, 0, 1) . "XX..XX" . substr($vir, -2);
                $formattedDate = Carbon::now()->format('d-m-Y H:i');
                $balance = number_format($balanceAction['final_balance'], 2);
                $amountFormatted = number_format($requestAmount, 2);
        
                $smsMessage = "Txn: Debit\n"
                    . "Ac:" . $maskedAccount . "\n"
                    . "Amt:NGN " . $amountFormatted . "\n"
                    . "Des:FUND WITHDRAWAL to {$request->bank_name}\n"
                    . "Date:" . $formattedDate . "\n"
                    . "Bal:NGN " . $balance;
        
                sendTransactionSms($user, $smsMessage, 1);
            }
            
            // The transfer has ALREADY completed at this point. The referral
            // commission is non-critical — if it throws, it must NOT 500 the
            // request (a 500 makes the client retry and re-send the money).
            try {
                referralCommission($user, $transaction, $requestAmount);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('referralCommission failed (transfer already succeeded): ' . $e->getMessage());
            }
        }
    
        (new FirebaseUserUpdate)->updateUser($user);
    
        return response()->json([
            'success' => $finalStatus === 'success',
            'status' => $finalStatus,
            'message' => 'Transaction unsuccessful: ' . $resDesc,
            'data' => [
                'service' => "Fund Withdrawal",
                'amount' => $requestAmount,
                'charge' => $charge + $p_charge,
                'reference' => $trx,
                'wallet_balance' => $balanceAction['final_balance'],
                'from' => "Wallet",
                'date' => $date,
                'to' => "{$request->account_number} {$request->account_name}",
            ],
            // 'response_data' => $response['data'],
        ]);
    }
     
    // public function transferBank(Request $request)
    // {
    //     $date = Carbon::now();
    //     $data = $request->all();
    
    //     $validatedData = Validator::make($data, [
    //         'wallet_id' => 'nullable|integer',
    //         'bank' => 'required|string',
    //         'bank_name' => 'required|string',
    //         'account_number' => 'required|string',
    //         'amount' => 'required|numeric|min:1',
    //         'comment' => 'nullable|string',
    //         'account_name' => 'required|string',
    //         'transaction_pin' => 'nullable|string',
    //     ]);
    
    //     if ($validatedData->fails()) {
    //         return response([
    //             'success' => false,
    //             'message' => implode(' ', $validatedData->errors()->all())
    //         ]);
    //     }
    
    //     $user = Auth::user();
    //     $walletId = $request->wallet_id ?? 1;
    
    //     $amountRaw = preg_replace('/[^\d.]/', '', $request->amount);
    //     $requestAmount = floatval($amountRaw);
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | TRANSACTION PIN CHECK
    //     |--------------------------------------------------------------------------
    //     */
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
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | SETTINGS CHECK
    //     |--------------------------------------------------------------------------
    //     */
    //     if (getSiteSettings("enable_withdrawal") !== "Yes") {
    //         return response(['success' => false, 'message' => "Withdrawals are currently disabled."]);
    //     }
    
    //     if ($requestAmount < getSiteSettings("min_withdrawal")) {
    //         return response(['success' => false, 'message' => "Minimum withdrawal is " . getSiteSettings("min_withdrawal")]);
    //     }
    
    //     if ($requestAmount > getSiteSettings("max_withdrawal")) {
    //         return response(['success' => false, 'message' => "Maximum withdrawal is " . getSiteSettings("max_withdrawal")]);
    //     }
    
    //     $limitCheck = checkDailyTransactionLimit($user, $requestAmount);
    //     if (!$limitCheck['success']) {
    //         return response(['success' => false, 'message' => $limitCheck['message']]);
    //     }
    
    //     if (!checkWalletEnable("WithdrawFund", $walletId)) {
    //         return response(['success' => false, 'message' => "Selected Wallet not allowed"]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | CHARGES
    //     |--------------------------------------------------------------------------
    //     */
    //     $charge = 0;
    //     $p_charge = 0;
    
    //     if (getSiteSettings("enable_withdrawal_charge") === "Yes") {
    //         $charge = getSiteSettings("default_withdrawal_charge");
    //     }
    
    //     if (getSiteSettings("enable_withdrawal_percent_charge") === "Yes") {
    //         $percent_charge = floatval(getSiteSettings("withdrawal_percent_charge"));
    //         $capped_charge = floatval(getSiteSettings("withdrawal_capped_amount"));
    
    //         $calculated = ($percent_charge / 100) * $requestAmount;
    //         $p_charge = $capped_charge > 0 ? min($calculated, $capped_charge) : $calculated;
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | VAT (ONLY ON CHARGES)
    //     |--------------------------------------------------------------------------
    //     */
    //     $vatAmount = 0;
    //     $vatPercent = getSiteSettings('vat_percent');
    //     if ($vatPercent > 0) {
    //         $vatAmount = ($vatPercent / 100) * ($charge + $p_charge);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | STAMP DUTY
    //     |--------------------------------------------------------------------------
    //     */
    //     $stampDuty = 0;
    //     $stampDutySetting = StampDutySetting::first();
    
    //     if ($stampDutySetting && $requestAmount >= $stampDutySetting->start) {
    //         $stampDuty = $stampDutySetting->charge;
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | TOTAL DEBIT CHECK (IMPORTANT)
    //     |--------------------------------------------------------------------------
    //     */
    //     $totalCharges = $charge + $p_charge + $vatAmount + $stampDuty;
    //     $totalDebit = $requestAmount + $totalCharges;
    
    //     if (!checkUserWallet($user, $totalDebit, $walletId)) {
    //         return response(['success' => false, 'message' => "Insufficient funds"]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | BALANCE ACTION
    //     |--------------------------------------------------------------------------
    //     */
    //     $balanceAction = userBalanceAction($user, $totalDebit, $walletId, "-");
    
    //     if (!$balanceAction['success']) {
    //         return response(['success' => false, 'message' => "Wallet balance error"]);
    //     }
    
    //     $trx = strtoupper(Str::random(16));
    //     $trxs = strtoupper(Str::random(16));
    //     $description = "Transfer to " . strtoupper($request->account_name);
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | MAIN WITHDRAWAL TRANSACTION
    //     |--------------------------------------------------------------------------
    //     */
    //     $transaction = $user->transaction()->create([
    //         'user_level_id' => $user->user_level_id,
    //         'wallet_id' => $walletId,
    //         'service' => 'System',
    //         'service_type' => 'Fund Withdrawal',
    //         'description' => $description,
    //         'amount' => $requestAmount,
    //         'real_price' => $requestAmount - ($charge + $p_charge),
    //         'charge' => $charge + $p_charge,
    //         'vat' => $vatAmount,
    //         'type' => 0,
    //         'initial_balance' => $balanceAction['init_balance'],
    //         'final_balance' => $balanceAction['final_balance'],
    //         'reference' => $trx,
    //         'status' => 'pending',
    //         'sender_bank_name' => 'Safe Haven MFB',
    //         'sender_account_name' => 'DU TRANSIT LTD / SUDO SETTLEMENT ACCOUNT',
    //         'sender_account_number' => '0118654890',
    //         'receiver_bank_name' => $request->bank_name,
    //         'receiver_account_name' => $request->account_name,
    //         'receiver_account_number' => $request->account_number,
    //     ]);
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | STAMP DUTY TRANSACTION (SEPARATE RECORD)
    //     |--------------------------------------------------------------------------
    //     */
    //     if ($stampDuty > 0) {
    //         $user->transaction()->create([
    //             'user_level_id' => $user->user_level_id,
    //             'wallet_id' => $walletId,
    //             'service' => 'System',
    //             'service_type' => 'Stamp Duty',
    //             'description' => "Stamp duty charge | Ref: #$trx",
    //             'amount' => $stampDuty,
    //             'type' => 0,
    //             'initial_balance' => $balanceAction['init_balance'],
    //             'final_balance' => $balanceAction['final_balance'],
    //             'reference' => $trxs,
    //             'status' => 'success',
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | AUTO WITHDRAWAL
    //     |--------------------------------------------------------------------------
    //     */
    //     $status = 0;
    //     $resDesc = "Processing your request";
    //     $finalStatus = "pending";
    //     $sessionId = $trx;
    
    //     if (getSiteSettings("enable_auto_withdrawal") === "Yes") {
    
    //         $response = sudoBankTransfer([
    //             'debitAccountId' => '68514e0fcb653e20408c42d0',
    //             'beneficiaryBankCode' => $request->bank,
    //             'beneficiaryAccountNumber' => $request->account_number,
    //             'amount' => $requestAmount,
    //             'narration' => $request->comment,
    //             'paymentReference' => $trx,
    //         ]);
    
    //         $resDesc = $response['message'];
    
    //         if ($response['success'] && isset($response['data']->data->sessionId)) {
    //             $sessionId = $response['data']->data->sessionId;
    //         }
    
    //         if ($response['success']) {
    //             $status = 1;
    //             $finalStatus = 'success';
    //         } elseif (getSiteSettings("enable_auto_withdrawal_refund") === "Yes") {
    //             $status = 4;
    //             $finalStatus = 'unsuccessful';
    //         }
    
    //         $transaction->update([
    //             'status' => $finalStatus,
    //             'session_id' => $sessionId
    //         ]);
    //     }
    
    //     /*
    //     |--------------------------------------------------------------------------
    //     | UPDATE FIREBASE USER
    //     |--------------------------------------------------------------------------
    //     */
    //     (new FirebaseUserUpdate)->updateUser($user);
    
    //     return response()->json([
    //         'success' => $finalStatus === 'success',
    //         'status' => $finalStatus,
    //         'message' => $resDesc,
    //         'data' => [
    //             'service' => "Fund Withdrawal",
    //             'amount' => $requestAmount,
    //             'charge' => $charge + $p_charge,
    //             'vat' => $vatAmount,
    //             'stamp_duty' => $stampDuty,
    //             'reference' => $trx,
    //             'wallet_balance' => $balanceAction['final_balance'],
    //         ],
    //     ]);
    // }


    public function transferFund(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'wallet_id' => '',
            'reciever_id' => 'required',
            'amount' => 'required',
            'comment' => '',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $requestAmount = str_replace(',', '', $request->amount);

        if (getSiteSettings("enable_transfer") != "Yes") {
            return response(['success' => false, 'message' => "Sorry Fund Transfer is dissabled at the moment try again later"]);
        }

        if (getSiteSettings("min_transfer") >  $requestAmount) {
            return response(['success' => false, 'message' => "Minimum Transfer amount is ".getSiteSettings("min_transfer")]);
        }

        if (getSiteSettings("max_transfer") <  $requestAmount) {
            return response(['success' => false, 'message' => "Maximum Transfer amount is ".getSiteSettings("max_transfer")]);
        }

        $select_wallet_id = $request->wallet_id ?? "1";
        if (!checkWalletEnable("TransferFund", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }

        $user = Auth::user();

        // Dupay-to-Dupay transfer had NO blocked-account check at all — every
        // other money route had one copy-pasted inline, this one was missed, so
        // a blocked user could still send money to another Dupay user.
        if (\App\Services\UserBlockService::isBlocked($user)) {
            return response(['success' => false, 'message' => \App\Services\UserBlockService::message()]);
        }

        if ($user->id == $request->reciever_id) {
            return response(['success' => false, 'message' => "You cant Transfer fund to yourself"]);
        }

        $reciever = User::find($request->reciever_id);

        if (!$reciever) {
            return response(['success' => false, 'message' => "Reciever not found"]);
        }

          //check transaction pin
          if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" ||  request("fingerPrint") != true) {
            if ($request->transaction_pin == null || $request->transaction_pin == "") {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }

        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            // if ($userTransaction->created_at > Carbon::now()->subSeconds(20)  &&  $userTransaction->service ==  "System")
            // {

            //     $tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
            //     return response(['success' => false, 'message' => "Probably Duplicate Transaction $tt try again later",]);

            // }
        }

        // Per-level DAILY transfer cap (separate from, and stricter than, the
        // overall daily transaction limit).
        //
        // Two bugs fixed here:
        //   * no whereDate() — it summed transfers for ALL TIME, so a user was
        //     permanently locked out of transfers once their lifetime total
        //     passed the cap, and it never reset at midnight;
        //   * it compared the running total to the cap without adding the amount
        //     being sent, so from a clean slate any single transfer went through
        //     however large.
        if(getSiteSettings("limit_daily_transfer") !== "No" && optional($user->level)->daily_transfer_limit && $user->level->daily_transfer_limit != "-"){
            $transferLimit  = (float) str_replace(',', '', (string) $user->level->daily_transfer_limit);
            $amountTransfer = (float) $user->transaction()
                ->where("service_type", "Fund Transfer")
                ->where("type", "0")
                ->where(function ($q) {
                    $q->where("currency", "NGN")->orWhereNull("currency")->orWhere("currency", "");
                })
                ->whereNotIn("status", \App\Services\TransactionLimitService::UNCOUNTED_STATUSES)
                ->whereDate("created_at", Carbon::today())
                ->sum("amount");

            if($transferLimit > 0 && ($amountTransfer + (float) $requestAmount) > $transferLimit){
                $remainingTransfer = max($transferLimit - $amountTransfer, 0);
                return response(['success' => false, 'message' =>
                    "Daily transfer limit exceeded. Your limit is ₦" . number_format($transferLimit, 2) .
                    " per day and you have already transferred ₦" . number_format($amountTransfer, 2) .
                    " today. You can still transfer up to ₦" . number_format($remainingTransfer, 2) . " today.",
                ]);
            }
        }

        // Overall daily transaction limit, shared with every other channel.
        $limitCheck = checkDailyTransactionLimit($user, $requestAmount);
        if (empty($limitCheck['success'])) {
            return response([
                'success'         => false,
                'message'         => $limitCheck['message'],
                'daily_limit'     => $limitCheck['limit'] ?? null,
                'spent_today'     => $limitCheck['spent'] ?? null,
                'remaining_limit' => $limitCheck['remaining'] ?? null,
            ]);
        }


        $amount =  str_replace(',', '', $request->amount);
       $comment = $request->comment;

       if(getSiteSettings("enable_transfer_charge") == "Yes"){
       $charge =  calCharge($amount, "1");
       if($user->level->transfer_charge > 0){
        $charge = $user->level->transfer_charge / 100 * ($amount + $charge);
       }
       if($charge < 1 && $user->level->transfer_charge < 1 ) {
        $charge = getSiteSettings("default_transfer_charge");
        }
        }else{
            //no transfer charge
            $charge = 0;
        }
        $amount =  $requestAmount + $charge;

        // $checkWallet =  checkUserWallet($amount, $select_wallet_id);
        $checkWallet = checkUserWallet($user, $amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }


        // === P2P transfer: atomic + idempotent ==================================
        // Whole transfer (debit + ledger rows + credit) runs in ONE DB transaction
        // with both wallet rows locked, so it either fully happens or not at all.
        // The duplicate guard blocks the SAME (sender -> receiver -> amount) transfer
        // fired again within 30s (double-tap / retry / rapid re-fire), which is how
        // a P2P could previously execute 2-3 times off one intent.
        try {
            $__t = \DB::transaction(function () use ($user, $reciever, $request, $amount, $charge, $comment, $select_wallet_id) {

                // Deadlock-safe: lock both rows in ascending id order.
                $__ids = [(int) $user->id, (int) $request->reciever_id];
                sort($__ids);
                \App\Models\User::whereIn('id', $__ids)->orderBy('id')->lockForUpdate()->get();

                // Duplicate guard (evaluated under the lock).
                $__dupe = \App\Models\Fintech\TransferFund::where('sender_id', $user->id)
                    ->where('reciever_id', $request->reciever_id)
                    ->where('amount', $request->amount)
                    ->where('created_at', '>=', Carbon::now()->subSeconds(30))
                    ->exists();
                if ($__dupe) {
                    return ['status' => 'duplicate'];
                }

                $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
                $init_balance = $balanceAction["init_balance"];
                $final_balance = $balanceAction["final_balance"];

                if (empty($balanceAction["success"])) {
                    // Carry the real reason out of the transaction. The debit can
                    // now also be refused for a blocked account or an exceeded
                    // daily limit, and reporting either as "Insufficient Funds"
                    // sends the user hunting for money they already have.
                    return [
                        'status'  => 'insufficient',
                        'reason'  => $balanceAction['reason']  ?? null,
                        'message' => $balanceAction['message'] ?? null,
                    ];
                }

                    $description = "Transfer of $request->amount to " . $reciever->profile->username . " $comment";
                    $trx = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Fund Transfer";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
                    $ul['real_price'] = $request->amount - $charge;
                    $ul['charge'] = $charge;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "0";
                    $ul['initial_balance'] = $init_balance;
                    $ul['final_balance'] = $final_balance;
                    $ul['reference'] = $trx;
                    $ul['status'] = "pending";
                    $sender_tansaction =  $user->transaction()->create($ul);
            
            


                    // $transer = $user->transferFund()->create([
                    //     "reciever_id" => $request->reciever_id,
                    //     "amount" => $request->amount,
                    //     "charge" => $charge,
                    //     'referrence' => $trx,
                    //     "comment" => $comment,
                    //     "status" => "1"
                    // ]);
            
                    $transer = \App\Models\Fintech\TransferFund::create([
            "sender_id"  => $user->id,
            "reciever_id" => $request->reciever_id,
            "amount" => $request->amount,
            "charge" => $charge,
            'referrence' => $trx,
            "comment" => $comment,
            "status" => "1"
        ]);


                    //reciver balance
                    $RbalanceAction =  userBalanceAction($reciever,$request->amount, $select_wallet_id);
                    $Rinit_balance = $RbalanceAction["init_balance"];
                    $Rfinal_balance = $RbalanceAction["final_balance"];

                    if (empty($RbalanceAction["success"])) {
                        throw new \RuntimeException('__transfer_rollback__'); // undo sender debit + rows
                    }

                    $description = "You Recieve sum of $request->amount naira from " . $user->profile->username . " $comment";
                    $trxR = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $reciever->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    // $ul['service_type'] = "Fund Transfer";
                    $ul['service_type'] = "Wallet Funding";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
                    $ul['real_price'] = $request->amount - $charge;
                    $ul['charge'] = $charge;
                    $ul['phone_number'] = $reciever->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $Rinit_balance;
                    $ul['final_balance'] = $Rfinal_balance;
                    $ul['reference'] = $trxR;
                    $ul['status'] = "pending";
                    $receiver_tansaction =  $reciever->transaction()->create($ul);

                return [
                    'status'               => 'ok',
                    'sender_tansaction'    => $sender_tansaction,
                    'receiver_tansaction'  => $receiver_tansaction,
                    'init_balance'         => $init_balance,
                    'final_balance'        => $final_balance,
                    'Rinit_balance'        => $Rinit_balance,
                    'Rfinal_balance'       => $Rfinal_balance,
                    'trx'                  => $trx,
                    'trxR'                 => $trxR,
                ];
            });
        } catch (\Throwable $e) {
            // Any failure rolled the whole transfer back — no money moved.
            \Illuminate\Support\Facades\Log::error('P2P transfer rolled back', ['sender' => $user->id, 'receiver' => $request->reciever_id, 'error' => $e->getMessage()]);
            return response(['success' => false, 'message' => "Transfer failed and was reversed. No money moved. Please try again."]);
        }

        if (($__t['status'] ?? '') === 'duplicate') {
            return response(['success' => false, 'message' => "Duplicate transfer blocked. Please check your history before retrying."]);
        }
        if (($__t['status'] ?? '') === 'insufficient') {
            return response([
                'success' => false,
                'message' => $__t['message'] ?? "Insufficient Funds",
            ]);
        }

        // Money moved atomically. Unpack for the notification + success block below.
        $sender_tansaction   = $__t['sender_tansaction'];
        $receiver_tansaction = $__t['receiver_tansaction'];
        $init_balance        = $__t['init_balance'];
        $final_balance       = $__t['final_balance'];
        $Rinit_balance       = $__t['Rinit_balance'];
        $Rfinal_balance      = $__t['Rfinal_balance'];
        $trx                 = $__t['trx'];
        $trxR                = $__t['trxR'];


            if($sender_tansaction && $receiver_tansaction){
                // Use model-instance ->update() (NOT a query-builder mass update)
                // so Eloquent's "updated" event fires and sendTransactionPush()
                // runs for BOTH parties: sender gets the Debit alert, receiver
                // gets the Credit alert. A ->where()->update() bypasses model
                // events, which is why P2P transfers previously sent no push.
                $sender_tansaction->update(['status' => "success"]);
                $receiver_tansaction->update(['status' => "success"]);


                if (getSiteSettings("email_notify") == "Yes") {
                    $p2pDate = Carbon::now()->format('d-M-Y h:i A');

                    // Sender — DEBIT ALERT (matches normal transfer format)
                    $senderMsg = "
                    <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Account Name</td><td style='padding:8px; border:1px solid #ddd;'><strong>" . ($reciever->profile->first_name ?? '') . " " . ($reciever->profile->last_name ?? '') . "</strong></td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td><td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td><td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($request->amount, 2) . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td><td style='padding:8px; border:1px solid #ddd;'>NGN</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td><td style='padding:8px; border:1px solid #ddd;'>FUND TRANSFER to " . $reciever->profile->username . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td><td style='padding:8px; border:1px solid #ddd;'>#" . $trx . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Date and Time</td><td style='padding:8px; border:1px solid #ddd;'>" . $p2pDate . "</td></tr>
                    </table><br><p>Thank you for using Dupay.</p>";

                    // Receiver — CREDIT ALERT (matches normal transfer format)
                    $receiverMsg = "
                    <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Account Name</td><td style='padding:8px; border:1px solid #ddd;'><strong>" . ($user->profile->first_name ?? '') . " " . ($user->profile->last_name ?? '') . "</strong></td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td><td style='padding:8px; border:1px solid #ddd;'>CREDIT ALERT</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td><td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($request->amount, 2) . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td><td style='padding:8px; border:1px solid #ddd;'>NGN</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td><td style='padding:8px; border:1px solid #ddd;'>FUND RECEIVED from " . $user->profile->username . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td><td style='padding:8px; border:1px solid #ddd;'>#" . $trxR . "</td></tr>
                        <tr><td style='padding:8px; border:1px solid #ddd;'>Date and Time</td><td style='padding:8px; border:1px solid #ddd;'>" . $p2pDate . "</td></tr>
                    </table><br><p>Thank you for using Dupay.</p>";

                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification', $senderMsg);
                        $this->sendMail($reciever->email, $reciever->profile->first_name, 'Dupay Credit Transaction Notification', $receiverMsg);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $p2pSmsDate = Carbon::now()->format('d-m-Y H:i');
                    $amtFmt     = number_format($request->amount, 2);

                    // Sender masked account (null-safe)
                    $sVir = optional($user->virtualAccount)->account_number;
                    $sMasked = $sVir ? substr($sVir, 0, 1) . "XX..XX" . substr($sVir, -2) : "-";

                    // Receiver masked account (null-safe)
                    $rVir = optional($reciever->virtualAccount)->account_number;
                    $rMasked = $rVir ? substr($rVir, 0, 1) . "XX..XX" . substr($rVir, -2) : "-";

                    // Sender — DEBIT
                    $senderSms = "Txn: Debit\n"
                        . "Ac:" . $sMasked . "\n"
                        . "Amt:NGN " . $amtFmt . "\n"
                        . "Des:FUND TRANSFER to " . $reciever->profile->username . "\n"
                        . "Date:" . $p2pSmsDate . "\n"
                        . "Bal:NGN " . number_format($final_balance, 2);

                    // Receiver — CREDIT
                    $receiverSms = "Txn: Credit\n"
                        . "Ac:" . $rMasked . "\n"
                        . "Amt:NGN " . $amtFmt . "\n"
                        . "Des:FUND RECEIVED from " . $user->profile->username . "\n"
                        . "Date:" . $p2pSmsDate . "\n"
                        . "Bal:NGN " . number_format($Rfinal_balance, 2);

                    try {
                        sendTransactionSms($user, $senderSms, 1);
                        sendTransactionSms($reciever, $receiverSms, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                //update firebase
                (new FirebaseUserUpdate)->updateUser($user);
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    "user" => $user,
                    'status' => 'successful',
                   
                    'message' => "Transfer Successful",
                    'data' => [
                        'service' => "Fund Transafer",
                        'amount' => $request->amount,
                        "charge" => $charge,
                        'from' => $user->profile->username,
                        "to" => $reciever->profile->username,
                        "for" => $request->comment,
                        'date' => $date,
                        'reference' => $trx,
                        'wallet_balance' => $Rfinal_balance,
                    ],
                ]);
            }


             $user->transaction()->where(['id' => $sender_tansaction->id])->update(['status' => "unsuccessful"]);
             $receiver_tansaction->delete();

             //add up refund
            $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id);
            $init_balance = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];

            $description = "Transfer of $request->amount naira to " . $reciever->profile->username . " Refund";
            $trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $reciever->user_level_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Fund Transfer";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $trx;
            $ul['status'] = "refund";
            $tansaction =  $user->transaction()->create($ul);

            //update firebase
            (new FirebaseUserUpdate)->updateUser($user);
            return response()->json([
                'success' => false,
                'status' => 'unsuccessful',
                'message' => 'Transaction unsuccessful',

                'data' => [
                    'referrence' => $trx,
                    'date' => $date
                ],
            ]);


    }



    /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *          Bank Transfer
     *
     *
     */

    //  public function getBankList(){
    //     //$banks = Flutterwave::banks()->nigeria();
    //     $banks = (new iSmartFlutterWave)->getBankList();
    //     if(!$banks['success']){
    //         return response(['success' => false, 'message' => $banks['message']]);
    //     }
    //     $user = Auth::user();
    //     $beneficiary = $user->beneficiary;

    //      return response(['success' => true, 'data' => $banks['data'], 'beneficiary' => $beneficiary]);
    //  }
     
    public function getBankList(){
        //$banks = Flutterwave::banks()->nigeria();
        //$banks = (new iSmartFlutterWave)->getBankList();
        $banks = fetchSafeHavenBanks();
        if(!$banks['success']){
            return response(['success' => false, 'message' => $banks['message']]);
        }
        $user = Auth::user();
        $beneficiary = $user->beneficiary;

         return response(['success' => true, 'data' => $banks['data'], 'beneficiary' => $beneficiary]);
    }


     public function transferUSDtoBankNG(){
        $data = [
            "account_number" =>  "3083936118",
            "account_bank" =>  "011",
            "narration" =>  "Nada",
            "amount" =>  1,
            "reference" =>  "khlggggm-dodddddddm-065",
            "currency" =>  "USD",
            "debit_currency" =>  "USD",
            "beneficiary_name" =>  "Ibrahim Yekeen Olamilekan",
            "meta" => [
              [
                "first_name" => "Ibrahim",
                "last_name" => "Yekeen",
                "email" => "ismartnaija@gmail.com",
                "beneficiary_country" => "NG",
                "mobile_number" => "+2349060341324",
                "sender" => "Olami",
                "merchant_name" => "PayDem"
              ]
            ]
          ];

          $transfer = Flutterwave::transfers()->initiate($data);

          return $transfer;
     }



    // public function bankTransfer(Request $request){

    //     $date = Carbon::now();
    //     $data = $request->all();
    //     $validatedData  = Validator::make($data, [
    //         'wallet_id' => '',
    //         'bank' => 'required',
    //         'bank_name' => 'required',
    //         'account_number' => 'required',
    //         'amount' => 'required',
    //         'comment' => '',
    //         'account_name' => 'required',
    //     ]);

    //     if ($validatedData->fails()) {
    //         $error = "";
    //         foreach ($validatedData->errors()->all() as $key => $value) {
    //             $error .= $value;
    //         }
    //         return response(['success' => false, 'message' => $error]);
    //     }

    //     $user = Auth::user();

    //     if(request("fingerPrint") != true){
    //         //check transaction pin
    //         if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api") {
    //             if ($request->transaction_pin == null || $request->transaction_pin == "") {
    //                 return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
    //             } elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
    //                 return response(['success' => false, 'message' => "Transaction Pin not set"]);
    //             } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
    //                 return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
    //             }
    //         }
    //     }

    //     $requestAmount = str_replace(',', '', $request->amount);

    //     if (getSiteSettings("enable_withdrawal") != "Yes") {
    //         return response(['success' => false, 'message' => "Sorry Withdrawal is disabled at the moment try again later"]);
    //     }

    //     if (getSiteSettings("min_withdrawal") >  $requestAmount) {
    //         return response(['success' => false, 'message' => "Minimum withdrawal is ".getSiteSettings("min_withdrawal")]);
    //     }

    //     if (getSiteSettings("max_withdrawal") <  $requestAmount) {
    //         return response(['success' => false, 'message' => "Maximum withdrawal is ".getSiteSettings("max_withdrawal")]);
    //     }

    //     //check level withdrawal limit
    //     if(getSiteSettings("limit_daily_withdrawal") == "Yes" && $user->level->daily_withdrawal_limit != "-"){
    //         $amountTransfer = $user->transaction()->where("service_type", "Fund Withdrawal")->where("type", "0")->sum("amount");
    //         if($amountTransfer >= $user->level->daily_withdrawal_limit){
    //             return response(['success' => false, 'message' => "Daily withdrawal limit exceeded"]);
    //         }
    //     }

    //     $select_wallet_id = $request->wallet_id ?? "1";
    //     if (!checkWalletEnable("WithdrawFund", $select_wallet_id)) {
    //         return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
    //     }

    //     $userTransaction = $user->transaction()->latest()->first();
    //     if($userTransaction){
    //         if ($userTransaction->created_at > Carbon::now()->subSeconds(30)  &&  $userTransaction->service ==  "System")
    //         {

    //             $tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
    //             return response(['success' => false, 'message' => "Probably Duplicate Transaction $tt try again later",]);

    //         }
    //   }
    //   $amount = $requestAmount;
    //   $bank = $request->bank;
    //   $bank_name = $request->bank_name;
    //   $account_name = $request->account_name;
    //   $account_number = $request->account_number;
    //   $comment = $request->comment;

    //   if(getSiteSettings("enable_withdrawal_charge") == "Yes"){
    //         $charge =  calCharge($amount, "2");
    //       if($user->level->withdrawal_charge > 0){
    //             $charge = $user->level->withdrawal_charge / 100 * ($amount + $charge);
    //       }
    //       if($charge < 1 && $user->level->withdrawal_charge < 1 ) {
    //             $charge = getSiteSettings("default_withdrawal_charge");
    //         }
    //     }else{
    //         //no withdrawal charge
    //         $charge = 0;
    //     }
        
    //     $p_charge = 0;

    //     if (getSiteSettings("enable_withdrawal_percent_charge") == "Yes") {
    //         $percent_charge = floatval(getSiteSettings("withdrawal_percent_charge"));
    //         $capped_charge = floatval(getSiteSettings("withdrawal_capped_amount"));
        
    //         $calculated_percent_charge = ($percent_charge / 100) * $requestAmount;
        
    //         $p_charge = $capped_charge > 0 ? min($calculated_percent_charge, $capped_charge) : $calculated_percent_charge;
    //     }
        
    //     $amount = $requestAmount + $charge + $p_charge;

    //     $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
    //     if (!$checkWallet) {
    //         return response(['success' => false, 'message' => "Insufficient Funds"]);
    //     }

    //     $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
    //     $init_balance = $balanceAction["init_balance"];
    //     $final_balance = $balanceAction["final_balance"];
    //     $resDesc = "processing your request";
    //     $status = "0";
    //     $finalStatus = "pending";

    //     if($balanceAction["success"] == false) {
    //         return response(['success' => false, 'message' => "Wallet balance error"]);
    //     }
    //         $description = "You transfer sum of $request->amount to $account_number $account_name";
    //         $trx = strtoupper(Str::random(16));
    //         $ul['user_level_id'] = $user->user_level_id;
    //         $ul['wallet_id'] = $select_wallet_id;
    //         $ul['service'] = "System";
    //         $ul['service_type'] = "Fund Withdrawal";
    //         $ul['description'] = $description;
    //         $ul['amount'] = $requestAmount;
    //         $ul['real_price'] = $requestAmount - $charge;
    //         $ul['charge'] = $charge + $p_charge;
    //         $ul['phone_number'] = $user->phone_number;
    //         $ul['type'] = "0";
    //         $ul['initial_balance'] = $init_balance;
    //         $ul['final_balance'] = $final_balance;
    //         $ul['reference'] = $trx;
    //         $ul['status'] = $finalStatus;
    //         $tansaction =  $user->transaction()->create($ul);
            
            

    //         //make automactic transfer to user bank
    //         if (getSiteSettings("enable_auto_withdrawal") == "Yes") {
    //             $res =  (new iSmartFlutterWave)->initiateTransferToBank($bank, $account_number, $requestAmount, $comment, $trx);
    //             $resDesc = $res["message"];
    //             if($res["success"] == true ){
    //                 $finalStatus = "success";
    //                 $status = "1"; //success
    //             }else{
    //                 if(getSiteSettings("enable_auto_withdrawal_refund") == "Yes"){
    //                     $finalStatus = "unsuccessful";
    //                     $status = "4"; //fail
    //                 }
    //             }
    //         }

    //             $withdraw = $user->withdrawalLog()->create([
    //                 "bank_code" => $bank,
    //                 "bank_name" => $bank_name,
    //                 "account_name" => $account_name,
    //                 "account_number" => $account_number,
    //                 "amount" => $requestAmount,
    //                 "charge" => $charge,
    //                 'referrence' => $trx,
    //                 "comment" => $comment,
    //                 "status" => $status
    //             ]);


    //             //update transaction status
    //             $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus]);
    //             $user->withdrawalLog()->where(['id' => $withdraw->id])->update(['status' => $status]);


    //           /* $user->beneficiary()->firstOrCreate([
    //                 "bank_code" => $bank,
    //                 "bank_name" => $bank_name,
    //                 "account_name" => $account_name,
    //                 "account_number" => $account_number,
    //                 "amount" => $requestAmount,
    //             ]);*/

    //             if($finalStatus != "unsuccessful"){
    //                 if (getSiteSettings("email_notify") == "Yes") {
    //                     $text = "You Withdraw sum of $request->amount naira into your " . $bank_name . " account  Reference ID Is : #$trx";
    //                     try {
    //                         $this->sendMail($user->email, $user->profile->first_name, 'Fund Withdrawal', $text);
    //                     } catch (\Throwable $th) {
    //                         //throw $th;
    //                     }
    //                 }
    //                 if (getSiteSettings("phone_notify") == "Yes") {
    //                     $text = "You Withdraw sum of $request->amount naira into your " . $bank_name . " account  Reference ID Is : #$trx";
    //                     try {
    //                         $this->sendSms($user->phone_number, $text);
    //                         sendTransactionSms($user, $text, 1);
    //                     } catch (\Throwable $th) {
    //                         //throw $th;
    //                     }
    //                 }

    //                 //update firebase
    //             (new FirebaseUserUpdate)->updateUser($user);
    //                 return response()->json([
    //                     'success' => true,
    //                     'code' => 200,
    //                     'status' => $finalStatus,
    //                      "user" => $user,
    //                      "message" => $resDesc,
    //                     'data' => [
    //                         'service' => "Fund Withdrawal",
    //                         'amount' => $request->amount,
    //                         "charge" => $charge,
    //                         'from' => "Wallet",
    //                         "to" => "$account_number $account_name",
    //                         'date' => $date,
    //                         'reference' => $trx,
    //                         'wallet_balance' => $final_balance,
    //                     ],
    //                 ]);

    //             }


    //                 //refund if fail
    //                 if(getSiteSettings("enable_auto_withdrawal_refund") == "Yes"){
    //                 $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
    //                 $init_balance = $balanceAction["init_balance"];
    //                 $final_balance = $balanceAction["final_balance"];
    //                 $description = "Withdrawal Request of $request->amount to your $bank_name account Refund";
    //                 $trx = strtoupper(Str::random(16));
    //                 $ul['user_level_id'] = $user->user_level_id;
    //                 $ul['wallet_id'] = $select_wallet_id;
    //                 $ul['service'] = "System";
    //                 $ul['service_type'] = "Fund Withdrawal";
    //                 $ul['description'] = $description;
    //                 $ul['amount'] = $request->amount;
    //                 $ul['phone_number'] = $user->phone_number;
    //                 $ul['type'] = "0";
    //                 $ul['initial_balance'] = $init_balance;
    //                 $ul['final_balance'] = $final_balance;
    //                 $ul['reference'] = $trx;
    //                 $ul['status'] = "refund";
    //                 $tansaction =  $user->transaction()->create($ul);
    //                 }

    //                 //update firebase
    //             (new FirebaseUserUpdate)->updateUser($user);
    //             return response()->json([
    //                 'success' => false,
    //                 'status' => 'unsuccessful',
    //                 'message' => 'Transaction unsuccessful '.$resDesc,
    //                 'data' => [
    //                     'reference' => $trx,
    //                     'date' => $date,
    //                     'from' => "Wallet",
    //                     "to" => "$account_number $account_name",
                        
    //                 ],
    //             ]);

    // }
    
    // public function transferBank(Request $request)
    // {
    //     $date = Carbon::now();
    //     $data = $request->all();
    
    //     $validatedData = Validator::make($data, [
    //         'wallet_id' => 'nullable|integer',
    //         'bank' => 'required|string',
    //         'bank_name' => 'required|string',
    //         'account_number' => 'required|string',
    //         'amount' => 'required|numeric|min:1',
    //         'comment' => 'nullable|string',
    //         'account_name' => 'required|string',
    //         'transaction_pin' => 'nullable|string',
    //     ]);
    
    //     if ($validatedData->fails()) {
    //         $error = implode(' ', $validatedData->errors()->all());
    //         return response(['success' => false, 'message' => $error]);
    //     }
    
    //     $user = Auth::user();
    //     $walletId = $request->wallet_id ?? 1;
    //     $amountRaw = preg_replace('/[^\d.]/', '', $request->amount);
    //     $requestAmount = floatval($amountRaw);
    
    //     //check transaction pin
    //     if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" ||  request("fingerPrint") != true) {
    //         if ($request->transaction_pin == null || $request->transaction_pin == "") {
    //             return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
    //         } elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
    //             return response(['success' => false, 'message' => "Transaction Pin not set"]);
    //         } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
    //             return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
    //         }
    //     }
    
    //     if (getSiteSettings("enable_withdrawal") !== "Yes") {
    //         return response(['success' => false, 'message' => "Withdrawals are currently disabled. Try again later."]);
    //     }
    
    //     if ($requestAmount < getSiteSettings("min_withdrawal")) {
    //         return response(['success' => false, 'message' => "Minimum withdrawal is " . getSiteSettings("min_withdrawal")]);
    //     }
    
    //     if ($requestAmount > getSiteSettings("max_withdrawal")) {
    //         return response(['success' => false, 'message' => "Maximum withdrawal is " . getSiteSettings("max_withdrawal")]);
    //     }
        
    //     $limitCheck = checkDailyTransactionLimit($user, $requestAmount);
    //     if (!$limitCheck['success']) {
    //         return response(['success' => false,
    //         'message' => $limitCheck['message']]);
    //     }
    
    //     // if (
    //     //     getSiteSettings("limit_daily_withdrawal") === "Yes" &&
    //     //     is_numeric($user->level->daily_withdrawal_limit)
    //     // ) {
    //     //     $dailyTotal = $user->transaction()
    //     //         ->where("service_type", "Fund Withdrawal")
    //     //         ->where("type", 0)
    //     //         ->whereDate('created_at', Carbon::today())
    //     //         ->sum("amount");
    
    //     //     if ($dailyTotal >= $user->level->daily_withdrawal_limit) {
    //     //         return response(['success' => false, 'message' => "Daily withdrawal limit exceeded"]);
    //     //     }
    //     // }
    
    //     if (!checkWalletEnable("WithdrawFund", $walletId)) {
    //         return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
    //     }
    
    
    //     $charge = 0;
    //     if (getSiteSettings("enable_withdrawal_charge") === "Yes") {
    //         // $charge = calCharge($requestAmount, "2");
    //         // if ($user->level->withdrawal_charge > 0) {
    //         //     $charge = $user->level->withdrawal_charge / 100 * ($requestAmount + $charge);
    //         // }
    //         // if ($charge < 1 && $user->level->withdrawal_charge < 1) {
    //         //     $charge = getSiteSettings("default_withdrawal_charge");
    //         // }
    //         $charge = getSiteSettings("default_withdrawal_charge");
    //     }
        
    //     if (getSiteSettings("enable_withdrawal_percent_charge") === "Yes") {
    //         $percent_charge = floatval(getSiteSettings("withdrawal_percent_charge"));
    //         $capped_charge = floatval(getSiteSettings("withdrawal_capped_amount"));
        
    //         $calculated_percent_charge = ($percent_charge / 100) * $requestAmount;
        
    //         $p_charge = $capped_charge > 0 ? min($calculated_percent_charge, $capped_charge) : $calculated_percent_charge;
    //     }
    
    //     $totalAmount = $requestAmount + $charge + $p_charge;
    
    //     if (!checkUserWallet($user, $totalAmount, $walletId)) {
    //         return response(['success' => false, 'message' => "Insufficient funds"]);
    //     }
    
    //     $balanceAction = userBalanceAction($user, $totalAmount, $walletId, "-");
    //     if (!$balanceAction['success']) {
    //         return response(['success' => false, 'message' => "Wallet balance error"]);
    //     }
    
    //     $trx = strtoupper(Str::random(16));
    //     $description = "Transfer to {strtoupper($request->account_name)}";
    
    //     $transaction = $user->transaction()->create([
    //         'user_level_id' => $user->user_level_id,
    //         'wallet_id' => $walletId,
    //         'service' => 'System',
    //         'service_type' => 'Fund Withdrawal',
    //         'description' => $description,
    //         'amount' => $requestAmount,
    //         'real_price' => $requestAmount - ($charge + $p_charge),
    //         'charge' => $charge + $p_charge,
    //         'phone_number' => $user->phone_number,
    //         'type' => 0,
    //         'initial_balance' => $balanceAction['init_balance'],
    //         'final_balance' => $balanceAction['final_balance'],
    //         'reference' => $trx,
    //         'status' => 'pending',
    //         'sender_bank_name' => 'Safe Haven MFB',
    //         'sender_account_name' => 'DU TRANSIT LTD / SUDO SETTLEMENT ACCOUNT',
    //         'sender_account_number' => '0118654890',
    //         'receiver_bank_name' => $request->bank_name,
    //         'receiver_account_name' => $request->account_name,
    //         'receiver_account_number' => $request->account_number,
    //     ]);
    
    //     $status = 0; // pending
    //     $resDesc = "Processing your request";
    //     $finalStatus = "pending";
    
    //     if (getSiteSettings("enable_auto_withdrawal") === "Yes") {
    //         // $response = (new iSmartFlutterWave)->initiateTransferToBank(
    //         //     $request->bank,
    //         //     $request->account_number,
    //         //     $requestAmount,
    //         //     $request->comment,
    //         //     $trx
    //         // );
            
    //         $response = sudoBankTransfer([
    //             'debitAccountId' => '68514e0fcb653e20408c42d0', 
    //             'beneficiaryBankCode' => $request->bank,
    //             'beneficiaryAccountNumber' => $request->account_number,
    //             'amount' => $requestAmount,
    //             'narration' => $request->comment,
    //             'paymentReference' => $trx,
    //         ]);
            
    //         $resDesc = $response['message'];
    //         $sessionId = $trx;
            
    //         if ($response['success'] && isset($response['data']->data->sessionId)) {
    //             $sessionId = $response['data']->data->sessionId;
    //         }
            
    //         if ($response['success']) {
    //             $status = 1;
    //             $finalStatus = 'success';
    //         } elseif (getSiteSettings("enable_auto_withdrawal_refund") === "Yes") {
    //             $status = 4;
    //             $finalStatus = 'unsuccessful';
    //         }
            
    //         if ($sessionId) {
    //             $transaction->update(['session_id' => $sessionId]);
    //         }

    //     }
    
    //     // Step 8: Log Withdrawal
    //     $withdrawal = $user->withdrawalLog()->create([
    //         "bank_code" => $request->bank,
    //         "bank_name" => $request->bank_name,
    //         "account_name" => $request->account_name,
    //         "account_number" => $request->account_number,
    //         "amount" => $requestAmount,
    //         "charge" => $charge,
    //         "referrence" => $trx,
    //         "comment" => $request->comment,
    //         "status" => $status,
    //     ]);
    
    //     $transaction->update(['status' => $finalStatus]);
    //     $withdrawal->update(['status' => $status]);
    
    //     if ($finalStatus === 'success') {
    //         $maskedAccount = substr($request->account_number ?? '1234567890', 0, 1) . "XX..XX" . substr($request->account_number ?? '1234567890', -2);
    //         $formattedDate = Carbon::now()->format('d-M-Y h:i A');
        
    //         $message = "
    //         <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
    //         <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'><strong>{$request->account_name}</strong></td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Amount</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>₦" . number_format($requestAmount, 2) . "</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Account Number</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>{$maskedAccount}</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>FUND WITHDRAWAL to {$request->bank_name}</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Transaction Remarks</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>{$sessionId}</td>
    //             </tr>
    //             <tr>
    //                 <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
    //                 <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
    //             </tr>
    //         </table>
    //         <br>
    //         <p>Thank you for using Dupay.</p>
    //         ";
    //         if (getSiteSettings("email_notify") === "Yes") {
    //             try {
    //                 $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification', $message);
    //             } catch (\Throwable $th) {
    //                 // Log or ignore
    //             }
    //         }
    //         if (getSiteSettings("phone_notify") === "Yes") {
    //             $vir = $user->virtualAccount->account_number;
    //             $maskedAccount = substr($vir, 0, 1) . "XX..XX" . substr($vir, -2);
    //             $formattedDate = Carbon::now()->format('d-m-Y H:i');
    //             $balance = number_format($balanceAction['final_balance'], 2);
    //             $amountFormatted = number_format($requestAmount, 2);
            
    //             $smsMessage = "Txn: Debit\n"
    //                 . "Ac:" . $maskedAccount . "\n"
    //                 . "Amt:NGN " . $amountFormatted . "\n"
    //                 . "Des:FUND WITHDRAWAL to {$request->bank_name}\n"
    //                 . "Date:" . $formattedDate . "\n"
    //                 . "Bal:NGN " . $balance;
            
    //             sendTransactionSms($user, $smsMessage, 1);
    //         }
    
    //         (new FirebaseUserUpdate)->updateUser($user);
    
    //         return response()->json([
    //             'success' => $response['success'],
    //             'code' => $response['success'] ? 200 : 400,
    //             'status' => $finalStatus,
    //             'message' => $response['message'] ?? $resDesc,
    //             'user' => $user,
    //             'data' => [
    //                 'service' => "Fund Withdrawal",
    //                 'amount' => $requestAmount,
    //                 'charge' => $charge + $p_charge,
    //                 'from' => "Wallet",
    //                 'to' => "{$request->account_number} {$request->account_name}",
    //                 'date' => $date->toDateTimeString(),
    //                 'reference' => $trx,
    //                 'wallet_balance' => $balanceAction['final_balance'],
    //                 'sudo_response' => $response['data'],
    //             ],
    //         ]);
    //     }
    
    //     if (getSiteSettings("enable_auto_withdrawal_refund") === "Yes") {
    //         $refund = userBalanceAction($user, $totalAmount, $walletId, "+");
    
    //         $user->transaction()->create([
    //             'user_level_id' => $user->user_level_id,
    //             'wallet_id' => $walletId,
    //             'service' => 'System',
    //             'service_type' => 'Fund Withdrawal',
    //             'description' => "Refund: Withdrawal of ₦{$request->amount} to {$request->bank_name} account failed",
    //             'amount' => $request->amount,
    //             'phone_number' => $user->phone_number,
    //             'type' => 0,
    //             'initial_balance' => $refund['init_balance'],
    //             'final_balance' => $refund['final_balance'],
    //             'reference' => strtoupper(Str::random(16)),
    //             'status' => 'refund',
    //         ]);
    //     }
    
    //     (new FirebaseUserUpdate)->updateUser($user);
    
    //     return response()->json([
    //         'success' => false,
    //         'status' => 'unsuccessful',
    //         'message' => 'Transaction unsuccessful: ' . $resDesc,
    //         'data' => [
    //             'reference' => $trx,
    //             'date' => $date,
    //             'from' => "Wallet",
    //             'to' => "{$request->account_number} {$request->account_name}",
    //         ],
    //     ]);
    // }


public function nameEnquiry(Request $request)
{
    $request->validate([
        'bank_code'      => 'required|string',
        'account_number' => 'required|string',
    ]);

    $result = safeHavenNameEnquiry($request->bank_code, $request->account_number);

    if ($result['success']) {
        return response()->json([
    'success' => true,
    'message' => $result['message'],
    'data'    => [
        'account_name' => $result['data']['account_name'],
        'session_id'   => $result['data']['session_id'],
    ],
]);
    }

    return response()->json([
        'success' => false,
        'message' => $result['message'] ?? 'Account verification failed',
    ]);
}

}