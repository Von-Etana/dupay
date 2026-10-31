<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CardWebhook;
use App\Models\PhysicalCard;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use App\Models\VirtualCardNGN;
use App\Models\VirtualCardUSD;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StroWalletWebhookController extends Controller
{
    use MailTrait;
    
    public function handleHook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }
    
        // ===================== 0. CARD BALANCE CHECK =====================
        // StroWallet asks for a card's spendable balance. For PHYSICAL cards the
        // spendable balance IS the user's NGN wallet balance (physical card
        // transactions debit $user->balance directly, see the authorization
        // path below). We must respond with exactly {"balance": <number>}.
        if (isset($payload['card.balance'])) {

            $cardId = $payload['card_Id'] ?? null;

            $card = $cardId ? PhysicalCard::where('card_id', $cardId)->first() : null;

            // If we can't resolve the card or user, report zero spendable —
            // never null, never an error shape — so the processor treats it as
            // "no funds" rather than retrying or crashing on our response.
            if (!$card || !$card->user) {
                Log::channel('strowallet')->warning('Balance check: card/user not found', ['card_Id' => $cardId]);
                return response()->json(['balance' => 0], 200);
            }

            // Cast to a clean 2-decimal value so StroWallet receives 104830.63
            // rather than the raw double's 104830.6300000000046566... tail.
            $balance = (float) number_format((float) $card->user->balance, 2, '.', '');

            return response()->json(['balance' => $balance], 200);
        }
    
        // ===================== 1. CARD AUTHORIZATION =====================
        if (isset($payload['authorization.request'])) {
            $cardId   = $payload['card_Id'] ?? null;
            $rawAmount = $payload['merchantAmount'] ?? null;
            $amount   = isset($payload['merchantAmount']) ? floatval($payload['merchantAmount']) : 0;
            $currency = strtoupper($payload['currency'] ?? 'NGN');
            $type     = $payload['type'] ?? 'unknown';
            $channel  = strtolower($payload['channel'] ?? '');
            $cardtype = strtolower($payload['cardtype'] ?? 'unknown');
            $charge   = floatval(getSiteSettings("atm_card_transaction_charge") ?? 0);
            $smsCharge = 0;
            $merchantData = $payload['merchant'] ?? null;
            $merchantName = $merchantData['name'] ?? 'Unknown Merchant';

            // ---------- AMOUNT VALIDATION ----------
            // Reject anything that is not a clean, positive numeric amount:
            // negatives, zero, symbols, letters, or non-numeric junk. is_numeric
            // rejects "-100", "1e5abc", "50#", "abc"; we then re-check > 0.
            $minWithdrawal = 50.0; // ₦50 minimum

            if (!is_numeric($rawAmount) || floatval($rawAmount) <= 0) {
                CardWebhook::create([
                    'card_id'                   => $cardId,
                    'amount'                    => 0,
                    'type'                      => $type,
                    'currency'                  => $currency,
                    'authorization_raw_payload' => json_encode($payload),
                    'status'                    => 'invalid_amount',
                ]);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Invalid transaction amount'], 200);
            }

            // Enforce a minimum only for NGN card withdrawals.
            if ($currency === 'NGN' && $amount < $minWithdrawal) {
                CardWebhook::create([
                    'card_id'                   => $cardId,
                    'amount'                    => $amount,
                    'type'                      => $type,
                    'currency'                  => $currency,
                    'authorization_raw_payload' => json_encode($payload),
                    'status'                    => 'below_minimum',
                ]);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Amount below minimum of 50 NGN'], 200);
            }
        
            $webhook = CardWebhook::create([
                'card_id'                   => $cardId,
                'amount'                    => $amount,
                'type'                      => $type,
                'currency'                  => $currency,
                'authorization_raw_payload' => json_encode($payload),
                'status'                    => 'received',
            ]);
        
            if ($cardtype === "physical") {
                $card = PhysicalCard::where('card_id', $cardId)
                    ->where('setup_step', '2')
                    ->first();
            } elseif ($cardtype === "virtual") {
                $card = VirtualCardNGN::where('card_id', $cardId)->first();
            } else {
                $webhook->update(['status' => 'unknown_card_type']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Unknown card type'], 200);
            }
        
            if (!$card || !$card->user) {
                $webhook->update(['status' => 'user_not_found']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'User not found'], 200);
            }
        
            if ($card->status !== 'active') {
                $webhook->update(['status' => 'card_not_active']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Card not active'], 200);
            }
        
            if ($card->blocked == true) {
                $webhook->update(['status' => 'card_blocked']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Card is blocked by user'], 200);
            }
        
            $allowedChannels = ['pos', 'atm', 'web', 'mobile'];
            if ($cardtype === 'physical' && in_array($channel, $allowedChannels) && !$card->{$channel}) {
                $webhook->update(['status' => 'channel_disabled']);
                return response()->json(['APPROVE' => 'NO', 'reason' => ucfirst($channel) . ' channel disabled'], 200);
            }
            
            
        
            $user = $card->user;
        
            if (isset($user->profile) && $user->profile->receive_sms == 1) {
                $smsCharge = floatval(getSiteSettings('sms_charges') ?? 0);
            }
        
            $totalDebit = $amount + $charge + $smsCharge;

            // Pre-check against the currently loaded balance (fast reject). The
            // authoritative check happens again UNDER THE ROW LOCK below, so a
            // concurrent auth can't slip through between here and the debit.
            if ($user->balance < $totalDebit) {
                $webhook->update(['status' => 'insufficient_balance']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Insufficient balance'], 200);
            }
            
            if($cardtype === 'physical' && $totalDebit > $card->spending_limit_amount){
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Card exceed spending limit'], 200);
            }
        
            $limitCheck = \App\Services\TransactionLimitService::check($user, $totalDebit, 'NGN');
            if (empty($limitCheck['success'])) {
                $webhook->update(['status' => 'daily_limit_exceeded']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Daily limit exceeded'], 200);
            }
        
            if (
                $cardtype === 'physical' &&
                $amount > floatval(getSiteSettings("physical_card_daily_limit") ?? 0)
            ) {
                $webhook->update(['status' => 'amount_exceeds_limit']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Amount exceeds daily limit'], 200);
            }
        
            // ---------- ATOMIC DEBIT UNDER ROW LOCK ----------
            // Re-reads the balance with SELECT ... FOR UPDATE, re-checks
            // sufficiency, and debits — all serialized per user. This is what
            // stops two simultaneous card authorizations from double-spending.
            $balanceAction = lockedWalletAction($user->id, $totalDebit, "-");
            if (!$balanceAction["success"]) {
                $webhook->update(['status' => 'balance_deduction_failed']);
                return response()->json(['APPROVE' => 'NO', 'reason' => 'Insufficient balance'], 200);
            }

            // Refresh the in-memory model so downstream notifications show the
            // post-debit balance written under the lock.
            $user->balance = $balanceAction["final_balance"];
        
            $Trx = strtoupper(Str::random(16));
            $formattedDate = Carbon::now()->format('d-M-Y h:i A');
            $formattedAmount = number_format($amount, 2);
            $formattedCharge = number_format($charge, 2);
            $formattedBalance = number_format($balanceAction["final_balance"], 2);
            // $maskedCard = $card->masked_pan ?? '**** **** **** ****';
            $maskedCard = $payload['maskedPan'] ?? ($card->masked_pan ?? '**** **** **** ****');
            $names = $user->profile->first_name.' '.$user->profile->last_name;
            
            $serv_type = ucwords($cardtype);
        
            // ✅ Save transaction
            $user->transaction()->create([
                "wallet_id"        => '1',
                "service"          => 'system',
                "service_type"     => "Naira {$serv_type} Card Withdraw",
                "description"      => "Card withdrawal from {$channel}",
                "amount"           => $amount,
                "real_price"       => $totalDebit,
                "charge"           => $charge,
                "type"             => "0",
                "initial_balance"  => $balanceAction["init_balance"],
                "final_balance"    => $balanceAction["final_balance"],
                "reference"        => $Trx,
                "status"           => 'success',
                "merchant"         => $merchantData ? json_encode($merchantData) : null,
            ]);
        
            /** ---------------- EMAIL NOTIFICATION ---------------- */
            if (getSiteSettings("email_notify") === "Yes") {
                $message = "
                <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong>:</p>
                <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                        <td style='padding:8px; border:1px solid #ddd;'><strong>{$names}</strong></td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                        <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Amount</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedAmount}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Card Number</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$maskedCard}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Narration</td>
                        <td style='padding:8px; border:1px solid #ddd;'>Card withdrawal from {$channel}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Reference</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$Trx}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Merchant</td>
                        <td style='padding:8px; border:1px solid #ddd;'><strong>{$merchantName}</strong></td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Date</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedBalance}</td>
                    </tr>
                </table>
                <p>Thank you for using Dupay.</p>";
        
                try {
                    $this->sendMail($user->email, $user->profile->username, 'Dupay Debit Transaction Notification', $message);
                } catch (\Throwable $th) {
                    // Optional: log the error
                }
            }
        
            /** ---------------- SMS NOTIFICATION ---------------- */
            if (getSiteSettings("phone_notify") === "Yes") {
                $maskedAccount = substr($card->card_pan, 0, 4) . "XX..XX" . substr($card->card_pan, -4);
                $formattedDateSMS = Carbon::now()->format('d-m-Y H:i');
                $smsMessage = "Txn: Debit\n"
                . "Card:{$maskedAccount}\n"
                . "Merchant:{$merchantName}\n"
                . "Amt:{$currency} {$formattedAmount}\n"
                . "Total:{$currency} " . number_format($totalDebit, 2) . "\n"
                . "Des:Card withdrawal from {$channel}\n"
                . "Date:{$formattedDateSMS}\n"
                . "Bal:{$currency} {$formattedBalance}";
        
                sendTransactionSms($user, $smsMessage, 1);
            }
        
            $webhook->update([
                'status' => 'approved',
                'transaction_reference' => $Trx,
            ]);
        
            return response()->json(['APPROVE' => 'YES'], 200);
        }
    
        // ===================== 2. TRANSACTION CREATED =====================
        elseif (isset($payload['transaction.refund'])) {
            // Determine card type
            if($payload['cardtype'] === "physical"){
                $card = PhysicalCard::where('card_id', $payload['card_Id'])->first();
            }
            elseif($payload['cardtype'] === "virtual"){
                $card = VirtualCardNGN::where('card_id', $payload['card_Id'])->first();
            }
            else{
                return response()->json(['APPROVE' => 'NO', 'reason' => 'UNKOWN CARD TYPE'], 200);
            }
        
            if (!$card || !$card->user) {
                return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
            }
        
            $user = $card->user;
            $amount = floatval($payload['merchantAmount']);

            // Guard: only credit a clean positive amount.
            if (!is_numeric($payload['merchantAmount'] ?? null) || $amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid refund amount'], 200);
            }

            // Add refund amount to user balance — atomic under row lock so a
            // refund cannot interleave with a concurrent debit and clobber it.
            $balanceAction = lockedWalletAction($user->id, $amount, "+");
            if (!$balanceAction["success"]) {
                return response()->json(['success' => false, 'message' => 'Refund failed'], 200);
            }
            $user->balance = $balanceAction["final_balance"];
        
            $Trx = strtoupper(Str::random(16));
            $channel = $payload['channel'] ?? 'unknown';
            $formattedDate = Carbon::now()->format('d-M-Y h:i A');
            $currency = 'NGN';
            $formattedAmount = number_format($amount, 2);
            $formattedBalance = number_format($balanceAction["final_balance"], 2);
        
            // Save transaction record
            $user->transaction()->create([
                "wallet_id"        => '1',
                "service"          => 'system',
                "service_type"     => 'refund',
                "description"      => "Card refund from {$channel}",
                "amount"           => $amount,
                "real_price"       => $amount,
                "charge"           => 0,
                "type"             => "1", // credit
                "initial_balance"  => $balanceAction["init_balance"],
                "final_balance"    => $balanceAction["final_balance"],
                "reference"        => $Trx,
                "status"           => 'success',
            ]);
        
            /** ---------------- Email Notification ---------------- */
            if (getSiteSettings("email_notify") === "Yes") {
                $message = "
                <p>This is to inform you that a refund has been processed on your account with <strong>Dupay</strong> with details below:</p>
                <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                        <td style='padding:8px; border:1px solid #ddd;'><strong>{$user->profile->username}</strong></td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                        <td style='padding:8px; border:1px solid #ddd;'>CREDIT ALERT (REFUND)</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Refund Amount</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedAmount}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                        <td style='padding:8px; border:1px solid #ddd;'>Card refund from {$channel}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$Trx}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedBalance}</td>
                    </tr>
                </table>
                <br>
                <p>Thank you for using Dupay.</p>
                ";
        
                try {
                    $this->sendMail($user->email, $user->profile->username, 'Dupay Credit Transaction Notification - Refund', $message);
                } catch (\Throwable $th) {
                    // Log or ignore
                }
            }
        
            /** ---------------- SMS Notification ---------------- */
            if (getSiteSettings("phone_notify") === "Yes") {
                $maskedAccount = substr($card->card_pan, 0, 4) . "XX..XX" . substr($card->card_pan, -4);
                $formattedDateSMS = Carbon::now()->format('d-m-Y H:i');
                $smsMessage = "Txn: Credit (Refund)\n"
                    . "Card:{$maskedAccount}\n"
                    . "Amt:{$currency} {$formattedAmount}\n"
                    . "Des:Card refund from {$channel}\n"
                    . "Date:{$formattedDateSMS}\n"
                    . "Bal:{$currency} {$formattedBalance}";
        
                sendTransactionSms($user, $smsMessage, 1);
            }
        
            return response()->json(['status' => true], 200);
        }
    
        // ===================== 3. TRANSACTION REFUND =====================
        elseif (isset($payload['transaction.refund'])) {
            $card = PhysicalCard::where('card_id', $payload['card_Id'])->first();
    
            if (!$card || !$card->user) {
                return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
            }
    
            $user = $card->user;
            $amount = floatval($payload['merchantAmount']);

            if (!is_numeric($payload['merchantAmount'] ?? null) || $amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid refund amount'], 200);
            }

            $balanceAction = lockedWalletAction($user->id, $amount, "+");
            if (!$balanceAction["success"]) {
                return response()->json(['success' => false, 'message' => 'Refund failed'], 200);
            }
            $user->balance = $balanceAction["final_balance"];
    
            $Trx = strtoupper(Str::random(16));
            $user->transaction()->create([
                "wallet_id" => '1',
                "service" => 'system',
                "service_type" => 'refund',
                "description" => "Card refund from " . ($payload['channel'] ?? 'unknown'),
                "amount" => $amount,
                "real_price" => $amount,
                "charge" => 0,
                "type" => "1", // credit
                "initial_balance" => $balanceAction["init_balance"],
                "final_balance" => $balanceAction["final_balance"],
                "reference" => $Trx,
                "status" => 'success',
            ]);
    
            return response()->json(['status' => true], 200);
        }
        
        
        // ===================== 4. VIRTUAL CARD WITHDRAWAL SUCCESS =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.withdrawal.success') {
    
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
            if (!$card) {
                return response()->json([
                    'status' => false,
                    'message' => "Virtual card not found for withdrawal: {$payload['cardId']}"
                ], 200);
            }
        
            $user = $card->user;
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => "User not found for virtual card: {$payload['cardId']}"
                ], 200);
            }
        
            $amount        = floatval($payload['amount'] ?? 0);
            $description   = $payload['narrative'] ?? 'Virtual Card Withdrawal';
            $reference     = $payload['reference'] ?? strtoupper(Str::random(16));
            
            if ($amount <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid amount received"
                ], 200);
            }
        
            // SEND EMAIL NOTIFICATION ONLY
            if (getSiteSettings("email_notify") === "Yes") {
        
                $formattedDate     = now()->format('d-M-Y h:i A');
                $formattedAmount   = number_format($amount, 2);
                $currentBalance    = number_format(floatval($card->balance), 2);
                $accountName       = strtoupper($user->profile->first_name . ' ' . $user->profile->last_name);
                $maskedCard        = substr($card->card_number ?? '', 0, 6) . '****' . substr($card->card_number ?? '', -4);
        
                $message = "
                    <p>This is to inform you that a transaction has occurred on your <strong>Dupay Virtual Card</strong>:</p>
                    
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>VIRTUAL CARD DEBIT ALERT</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Amount Debited</td>
                            <td style='padding:8px; border:1px solid #ddd;'>USD {$formattedAmount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Card Number</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$maskedCard}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$reference}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                    </table>
                    
                    <br>
                    <p>Thank you for choosing <strong>Dupay</strong>. We appreciate your continued trust.</p>
                ";
        
                $subject = "Dupay Virtual Card Debit Transaction Notification";
                
                try {
                    $this->sendMail($user->email, $user->profile->username ?? $user->email, $subject, $message);
                } catch (\Throwable $th) {
                    // Log email error but don't fail the webhook
                    \Log::error("Failed to send virtual card withdrawal email: " . $th->getMessage());
                }
            }
        
            // SMS NOTIFICATION
            // if (getSiteSettings("phone_notify") === "Yes") {
            //     $formattedDateSMS = now()->format('d-m-Y H:i');
            //     $currentBalance = number_format(floatval($card->balance), 2);
            //     $smsMessage = "Virtual Card Debit\n"
            //         . "Amt:USD {$formattedAmount}\n"
            //         . "Card:{$maskedCard}\n"
            //         . "Des:{$description}\n"
            //         . "Date:{$formattedDateSMS}\n"
            //         . "Bal:USD {$currentBalance}";
        
            //     sendTransactionSms($user, $smsMessage, 1);
            // }
        
            return response()->json([
                'status' => true,
                'message' => 'Virtual card withdrawal notification processed successfully'
            ], 200);
        }
    
        // ===================== 4. VIRTUAL CARD CREATED SUCCESS =====================
        // Match both payload shapes StroWallet has been seen to send.
        elseif (isset($payload['virtualcard.created.success'])
            || (($payload['event'] ?? null) === 'virtualcard.created.success')) {

            $cardId = $payload['cardId'] ?? $payload['card_Id'] ?? null;

            // Primary: the row already carries this card_id (job finalised it).
            $card = $cardId
                ? VirtualCardUSD::where('card_id', $cardId)->first()
                : null;

            // Reconcile the timeout case: if create-card timed out, the job left
            // a `pending` row with NO card_id. Adopt this webhook's card_id onto
            // the user's pending row so the card isn't orphaned.
            if (!$card) {
                $email = $payload['customerEmail'] ?? $payload['customer_email'] ?? null;
                $user  = $email ? \App\Models\User::where('email', $email)->first() : null;

                if ($user) {
                    $card = VirtualCardUSD::where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->latest('id')
                        ->first();

                    if ($card && $cardId) {
                        $card->card_id = $cardId;
                    }
                }
            }

            if (!$card || !$card->user) {
                return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
            }

            // Finalise the row regardless of which path found it.
            if ($card->status !== 'active') {
                $card->status = 'active';
            }
            $card->save();

            $user = $card->user;

            $message = "USD Virtual Card created successfully. Find card details in your account.";

            $this->sendMail(
                $user->email,
                $user->profile->username ?? "",
                "Virtual card created successfully",
                $message
            );

            return response()->json(['status' => true, 'message' => 'Virtual card created successfully'], 200);
        }
    
        // ===================== 5. VIRTUAL CARD CREATED FAILED =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.created.failed') {
            
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
    
            if (!$card || !$card->user) {
                return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
            }
    
            $user = $card->user;
            
            $message = "USD Virtual Card created failed. Reason: " . $payload['reason'];
            
            $this->sendMail(
                $user->email,
                $user->profile->username ?? "",
                "Virtual card failed",
                $message
            );
    
            return response()->json([
                'status'  => false,
                'message' => 'Virtual card creation failed',
                'reason'  => $payload['reason'] ?? null,
            ], 200);
        }
    
        // ===================== 6. VIRTUAL CARD TOPUP SUCCESS =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.topup.success') {
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
    
            if (!$card) {
                return response()->json(['status' => false, 'message' => 'Virtual card not found'], 200);
            }
    
            $amount = floatval($payload['amount']);
            $user = $card->user;
    
            $Trx = strtoupper(Str::random(16));
            $user->transaction()->create([
                "wallet_id" => '1',
                "service" => 'system',
                "service_type" => 'Dollar Virtual Card Topup',
                "description" => "Virtual card topup",
                "amount" => $amount,
                "real_price" => $amount,
                "charge" => 0,
                "type" => "1",
                "initial_balance" => $card->balance,
                "final_balance" => $card->balance + $amount,
                "reference" => $payload['reference'],
                "status" => 'success',
                "currency" => "USD",
            ]);
            
            if (getSiteSettings("email_notify") == "Yes") {
                $actionWord = "CREDIT";
                $formattedDate = now()->format('d-M-Y h:i A');
                $formattedAmount = number_format($amount, 2);
                $trx = $payload['reference'];
                $accountName = strtoupper($user->profile->first_name . ' ' . $user->profile->last_name);
                $description = $payload['narrative'];
        
                $message = "
                    <p>This is to inform you that a transaction has occurred on your <strong>Dupay Account</strong> with the details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>VIRTUAL CARD {$actionWord} ALERT</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Amount Received</td>
                            <td style='padding:8px; border:1px solid #ddd;'>USD {$formattedAmount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                            <td style='padding:8px; border:1px solid #ddd;'>USD</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$description}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$trx}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                    </table>
                    <br>
                    <p>Thank you for choosing <strong>Dupay</strong>. We appreciate your trust in our services.</p>
                ";
        
                $subject = "Dupay Virtual Card Credit Transaction Notification";
                $this->sendMail($user->email, $user->profile->username ?? '', $subject, $message);
            }
    
            return response()->json(['status' => true, 'message' => 'Virtual card topped up successfully'], 200);
        }
        
        // ===================== 6. VIRTUAL CARD TRANSACTION DEBIT =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.transaction.debit') {

            Log::info("Virtual card debit notification received");
        
            // Validate cardId
            if (!isset($payload['cardId'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'cardId missing from payload'
                ], 422);
            }
        
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
            if (!$card) {
                Log::warning("Virtual Card not found for: {$payload['cardId']}");
                return response()->json([
                    'status' => false,
                    'message' => "Virtual card not found for transaction: {$payload['cardId']}"
                ], 200);
            }
        
            $user = $card->user;
        
            // Check if user exists
            if (!$user) {
                Log::warning("User not found for virtual card: {$payload['cardId']}");
                return response()->json([
                    'status' => false,
                    'message' => "User not found for virtual card"
                ], 200);
            }
        
            // SAFE VALUES
            $amount      = floatval($payload['amount'] ?? 0);
            $reference   = $payload['reference'] ?? strtoupper(Str::random(16));
            $description = $payload['narrative'] ?? 'Virtual Card Transaction Debit';
        
            // Prevent negative or zero-amount transactions
            if ($amount <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid amount received"
                ], 200);
            }
        
            // EMAIL NOTIFICATION ONLY - NO BALANCE UPDATES OR TRANSACTION CREATION
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $formattedDate    = now()->format('d-M-Y h:i A');
                    $formattedAmount  = number_format($amount, 2);
                    $currentBalance   = number_format(floatval($card->balance), 2);
                    $accountName      = strtoupper($user->profile->first_name . ' ' . $user->profile->last_name);
                    $maskedCard       = substr($card->card_number ?? '', 0, 6) . '****' . substr($card->card_number ?? '', -4);
        
                    $message = "
                        <p>This is to inform you that a debit transaction has occurred on your <strong>Dupay Virtual Card</strong>:</p>
                        <table style='width:100%; border-collapse: collapse; font-family: Arial; font-size: 14px;'>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Account Name</td><td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td><td style='padding:8px; border:1px solid #ddd;'>VIRTUAL CARD DEBIT ALERT</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Amount Charged</td><td style='padding:8px; border:1px solid #ddd;'>USD {$formattedAmount}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Card Number</td><td style='padding:8px; border:1px solid #ddd;'>{$maskedCard}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Narration</td><td style='padding:8px; border:1px solid #ddd;'>{$description}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Reference</td><td style='padding:8px; border:1px solid #ddd;'>{$reference}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Date & Time</td><td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td></tr>
                        </table>
                        <br>
                        <p>Thank you for using <strong>Dupay</strong>.</p>
                    ";
        
                    $subject = "Dupay Virtual Card Debit Transaction Notification";
        
                    $this->sendMail($user->email, $user->profile->username ?? $user->email, $subject, $message);
                    
                    Log::info("Virtual Card Debit Notification Email Sent", [
                        'card_id' => $payload['cardId'],
                        'user_email' => $user->email,
                        'amount' => $amount
                    ]);
                } catch (\Exception $e) {
                    Log::error("Email Notification Error: " . $e->getMessage());
                }
            }
        
            return response()->json([
                'status' => true,
                'message' => 'Debit notification processed successfully'
            ], 200);
        }
    
        // ===================== 7. VIRTUAL CARD TERMINATED =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.transaction.declined.terminated') {
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
    
            if ($card) {
                $card->update(['status' => 'terminated']);
                // $card->terminations()->create([
                //     'reference'      => $payload['reference'],
                //     'transaction_id' => $payload['transactionReference'],
                //     'amount'         => $payload['amount'],
                //     'balance_before' => $payload['balanceBeforeTermination'] ?? null,
                //     'status'         => $payload['status'],
                // ]);
                
                $message = "We are writing to inform you that your USD virtual card ending in ****[last 4 digits] has been terminated. Please note that the card is no longer active and cannot be used for any future transactions. If you have any recurring payments linked to this card, we recommend updating your payment details as soon as possible to avoid service interruptions.";
            
                $this->sendMail(
                    $user->email,
                    $user->profile->username ?? "",
                    "Notice of USD Virtual Card Termination",
                    $message
                );
            }
    
            return response()->json(['status' => true, 'message' => 'Virtual card terminated'], 200);
        }
    
        // ===================== 8. VIRTUAL CARD CROSSBORDER FEE =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.transaction.crossborder') {
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
    
            if ($card) {
                $card->fees()->create([
                    'reference'     => $payload['reference'],
                    'amount'        => $payload['chargedAmount'] ?? $payload['amount'],
                    'narrative'     => $payload['narrative'],
                    'status'        => $payload['status'],
                    'transaction_id'=> $payload['transactionReference'] ?? null,
                ]);
            }
    
            return response()->json(['status' => true, 'message' => 'Crossborder fee recorded'], 200);
        }
    
        // ===================== 9. VIRTUAL CARD DECLINED =====================
        elseif (($payload['event'] ?? null) === 'virtualcard.transaction.declined') {
            
            $card = VirtualCardUSD::where('card_id', $payload['cardId'])->first();
        
            if (!$card) {
                Log::warning("Virtual Card not found for declined transaction: {$payload['cardId']}");
                return response()->json([
                    'status' => false,
                    'message' => "Virtual card not found"
                ], 200);
            }
        
            $user = $card->user;
        
            if (!$user) {
                Log::warning("User not found for virtual card: {$payload['cardId']}");
                return response()->json([
                    'status' => false,
                    'message' => "User not found for virtual card"
                ], 200);
            }
        
            // EMAIL NOTIFICATION FOR DECLINED TRANSACTION
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $amount = floatval($payload['amount'] ?? 0);
                    $formattedAmount = number_format($amount, 2);
                    $formattedDate = now()->format('d-M-Y h:i A');
                    $reference = $payload['reference'] ?? 'N/A';
                    $reason = $payload['reason'] ?? 'Unknown reason';
                    $narrative = $payload['narrative'] ?? 'Transaction attempted';
                    $accountName = strtoupper($user->profile->first_name . ' ' . $user->profile->last_name);
                    $maskedCard = substr($card->card_number ?? '', 0, 6) . '****' . substr($card->card_number ?? '', -4);
        
                    $message = "
                        <p>This is to inform you that a transaction attempt on your <strong>Dupay Virtual Card</strong> was declined:</p>
                        <table style='width:100%; border-collapse: collapse; font-family: Arial; font-size: 14px;'>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Account Name</td><td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td><td style='padding:8px; border:1px solid #ddd;'>VIRTUAL CARD DECLINED TRANSACTION</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Amount Attempted</td><td style='padding:8px; border:1px solid #ddd;'>USD {$formattedAmount}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Card Number</td><td style='padding:8px; border:1px solid #ddd;'>{$maskedCard}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Transaction Narration</td><td style='padding:8px; border:1px solid #ddd;'>{$narrative}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Decline Reason</td><td style='padding:8px; border:1px solid #ddd;'>{$reason}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Reference</td><td style='padding:8px; border:1px solid #ddd;'>{$reference}</td></tr>
                            <tr><td style='padding:8px; border:1px solid #ddd;'>Date & Time</td><td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td></tr>
                        </table>
                        <br>
                        <p>If you did not initiate this transaction, please contact our support team immediately.</p>
                        <p>Thank you for using <strong>Dupay</strong>.</p>
                    ";
        
                    $subject = "Dupay Virtual Card Transaction Declined Notification";
        
                    $this->sendMail($user->email, $user->profile->username ?? $user->email, $subject, $message);
                    
                    Log::info("Virtual Card Decline Notification Email Sent", [
                        'card_id' => $payload['cardId'],
                        'user_email' => $user->email,
                        'amount' => $amount,
                        'reason' => $reason
                    ]);
        
                } catch (\Exception $e) {
                    Log::error("Decline Email Notification Error: " . $e->getMessage());
                }
            }
        
            return response()->json([
                'status' => true,
                'message' => 'Virtual card decline notification processed successfully'
            ], 200);
        }
    
        // ===================== 10. UNKNOWN EVENT =====================
        return response()->json(['status' => false, 'message' => 'Unknown event type'], 200);
    }
    
    // public function handleHook(Request $request)
    // {
    //     Log::channel('strowallet')->info('Webhook Payload:', ['payload' => $request->all()]);
    //     $payload = json_decode($request->getContent(), true);
    
    //     // ===================== 1. CARD AUTHORIZATION =====================
    //     if (isset($payload['authorization.request'])) {
    //         $cardId   = $payload['card_Id'] ?? null;
    //         $amount   = isset($payload['merchantAmount']) ? floatval($payload['merchantAmount']) : 0;
    //         $currency = $payload['currency'] ?? 'NGN';
    //         $type     = $payload['type'] ?? 'unknown';
    //         $channel  = strtolower($payload['channel'] ?? '');
    
    //         $webhook = CardWebhook::create([
    //             'card_id'               => $cardId,
    //             'amount'                => $amount,
    //             'type'                  => $type,
    //             'currency'              => $currency,
    //             'authorization_raw_payload' => json_encode($payload),
    //             'status'                => 'received',
    //         ]);
    
    //         $card = PhysicalCard::where('card_id', $cardId)->first();
    
    //         if (!$card || !$card->user) {
    //             $webhook->update(['status' => 'user_not_found']);
    //             return response()->json(['APPROVE' => 'NO', 'reason' => 'User not found'], 200);
    //         }
    
    //         if ($card->status !== 'active') {
    //             $webhook->update(['status' => 'card_not_active']);
    //             return response()->json(['APPROVE' => 'NO', 'reason' => 'Card not active'], 200);
    //         }
    
    //         // Channel check
    //         $channelFields = ['pos', 'atm', 'web', 'mobile'];
    //         if (in_array($channel, $channelFields) && !$card->{$channel}) {
    //             $webhook->update(['status' => 'channel_disabled']);
    //             return response()->json(['APPROVE' => 'NO', 'reason' => ucfirst($channel) . ' channel disabled'], 200);
    //         }
    
    //         $user = $card->user;
    //         if (!$user->hasVerifyWallet) {
    //             return response()->json(['APPROVE' => 'NO', 'reason' => 'User KYC not approved'], 200);
    //         }
    
    //         if ($user->balance < $amount) {
    //             $webhook->update(['status' => 'insufficient_balance']);
    //             return response()->json(['APPROVE' => 'NO', 'reason' => 'Insufficient balance'], 200);
    //         }
    
    //         if (getSiteSettings("limit_daily_transaction") === "Yes" && $user->level && $user->level->daily_transaction_limit != "-") {
    //             $totalTransaction = $user->transaction()
    //                 ->where("type", "0")
    //                 ->where("status", "success")
    //                 ->whereDate('created_at', Carbon::today())
    //                 ->sum("amount");
    
    //             if ($totalTransaction >= $user->level->daily_transaction_limit &&
    //                 (!$user->walletVerification || $user->walletVerification->status != "1")) {
    //                 return response()->json(['APPROVE' => 'NO', 'reason' => 'Daily limit exceeded'], 200);
    //             }
    //         }
    
    //         if ($amount > getSiteSettings("physical_card_daily_limit")) {
    //             return response()->json(['APPROVE' => 'NO', 'reason' => 'Amount exceeds daily limit'], 200);
    //         }
    
    //         $webhook->update(['status' => 'approved', 'transaction_reference' => $payload['authorization.request'] ?? null]);
    //         return response()->json(['APPROVE' => 'YES'], 200);
    //     }
    
    //     // ===================== 2. TRANSACTION CREATED =====================
    //     elseif (isset($payload['transaction.created'])) {
    //         $card = PhysicalCard::where('card_id', $payload['card_Id'])->first();
    
    //         if (!$card || !$card->user) {
    //             return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
    //         }
    
    //         $user = $card->user;
    //         $amount = floatval($payload['merchantAmount']);
    
    //         $balanceAction = userBalanceAction($user, $amount, '1', "-");
    //         if (!$balanceAction["success"]) {
    //             return response()->json(['success' => false, 'message' => 'Insufficient balance'], 200);
    //         }
    
    //         $Trx = strtoupper(Str::random(16));
    //         $user->transaction()->create([
    //             "wallet_id" => '1',
    //             "service" => 'system',
    //             "service_type" => 'withdraw',
    //             "description" => "Card withdraw from " . ($payload['channel'] ?? 'unknown'),
    //             "amount" => $amount,
    //             "real_price" => $amount,
    //             "charge" => 0,
    //             "type" => "1",
    //             "initial_balance" => $balanceAction["init_balance"],
    //             "final_balance" => $balanceAction["final_balance"],
    //             "reference" => $Trx,
    //             "status" => 'success',
    //         ]);
    
    //         return response()->json(['status' => true], 200);
    //     }
    
    //     // ===================== 3. TRANSACTION REFUND =====================
    //     elseif (isset($payload['transaction.refund'])) {
    //         $card = PhysicalCard::where('card_id', $payload['card_Id'])->first();
    
    //         if (!$card || !$card->user) {
    //             return response()->json(['status' => false, 'message' => 'Card or user not found.'], 200);
    //         }
    
    //         $user = $card->user;
    //         $amount = floatval($payload['merchantAmount']);
    
    //         $balanceAction = userBalanceAction($user, $amount, '1', "+");
    //         if (!$balanceAction["success"]) {
    //             return response()->json(['success' => false, 'message' => 'Refund failed'], 200);
    //         }
    
    //         $Trx = strtoupper(Str::random(16));
    //         $user->transaction()->create([
    //             "wallet_id" => '1',
    //             "service" => 'system',
    //             "service_type" => 'refund',
    //             "description" => "Card refund from " . ($payload['channel'] ?? 'unknown'),
    //             "amount" => $amount,
    //             "real_price" => $amount,
    //             "charge" => 0,
    //             "type" => "1", // credit
    //             "initial_balance" => $balanceAction["init_balance"],
    //             "final_balance" => $balanceAction["final_balance"],
    //             "reference" => $Trx,
    //             "status" => 'success',
    //         ]);
    
    //         return response()->json(['status' => true], 200);
    //     }
    
    //     // ===================== 4. UNKNOWN EVENT =====================
    //     return response()->json(['status' => false, 'message' => 'Unknown event type'], 200);
    // }
    
}
