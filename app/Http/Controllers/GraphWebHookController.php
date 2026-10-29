<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TraitsFolder\MailTrait;
use App\Models\ForeignAccount;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\DepositFee;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Services\GraphDepositVerification;

class GraphWebHookController extends Controller
{
    use MailTrait;
    
    public function handle(Request $request)
    {
        Log::info('Graph webhook received');
        
        $payload = $request->all();
        $event = $payload['event_type'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$event || empty($data)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $foreignAccount = null;
        if ($event === 'account.credit') {
            $foreignAccount = ForeignAccount::where('account_id', $data['account_id'] ?? null)->first();
            $user = $foreignAccount?->user;
        } else {
            $user = User::where('graph_person_id', $data['holder_id'] ?? null)->first();
        }

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($event === 'account.created') {
            $account = ForeignAccount::updateOrCreate(
                ['account_id' => $data['id']],
                [
                    'user_id'        => $user->id,
                    'account_id'     => $data['id'],
                    'holder_id'      => $data['holder_id'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'account_name'   => $data['account_name'] ?? null,
                    'bank_name'      => $data['bank_name'] ?? null,
                    'bank_code'      => $data['bank_code'] ?? null,
                    'routing_number' => $data['routing_number'] ?? null,
                    'bank_address'   => json_encode($data['bank_address'] ?? []),
                    'balance'        => $data['balance'] ?? 0,
                    'currency'       => $data['currency'] ?? null,
                    'status'         => $data['status'] ?? 'active',
                    'bank_data'        => json_encode($data),
                ]
            );

            if (getSiteSettings("email_notify") == "Yes") {
                $message = "We are pleased to inform you that your foreign account has been successfully created. 🎉\n\n"
                    . "Account Details:\n"
                    . "Account Number: {$account->account_number}\n"
                    . "Bank Name: {$account->bank_name}\n"
                    . "Currency: {$account->currency}\n\n"
                    . "You can now start receiving payments directly into this account.\n\n"
                    . "Thank you for choosing " . config('app.name') . ".";

                $this->sendMail($user->email, $user->profile->first_name ?? "", "Your Foreign Account Has Been Successfully Created", $message);
            }
        }

        elseif ($event === 'account.issuance.failed') {
            
            $user->graph_person_id = NULL;
            $user->save();

            if (getSiteSettings("email_notify") == "Yes") {
                $reason = $data['status_message'] ?? "The Bank failed to issue your account.";

                $message = "We regret to inform you that your foreign account request has failed.\n\n"
                    . "Reason: {$reason}\n\n"
                    . "If you believe this is a mistake or you'd like clarification, please contact our support team.\n\n"
                    . "Thank you for your understanding.\n\n"
                    . "Regards,\n" . config('app.name');

                $this->sendMail($user->email, $user->profile->first_name ?? "", "Your Foreign Account Request Failed", $message);
            }
        }
        
        elseif ($event === 'account.credit') {
            $data = $payload['data'] ?? [];
        
            $accountId   = $data['account_id'] ?? null;
            $depositId = $data['deposit_id'] ?? ($data['deposit']['id'] ?? null);
            if (!is_string($depositId) || !preg_match('/\A[a-zA-Z0-9_-]{8,128}\z/D', $depositId)) {
                return response()->json(['error' => 'Deposit reference required'], 422);
            }
            // A webhook is untrusted input. Confirm the deposit with Graph's
            // authenticated API before changing any Dupay balance.
            $verified = fetchGraphDeposit($depositId);
            $settledMinor = GraphDepositVerification::settledMinorUnits(
                array_merge($data, ['deposit_id' => $depositId]), $verified
            );
            if ($settledMinor === null) {
                return response()->json(['error' => 'Deposit could not be verified'], 503);
            }
            // Graph reports amounts in minor units. Its settled amount already
            // excludes the provider fee and is what reached the account.
            $amountPaid = $settledMinor / 100;
            $currency    = strtoupper($data['currency'] ?? 'USD');
            $description = $data['description'] ?? 'Account credited';
            $isCredit    = true;
        
            if (!$foreignAccount || !in_array($currency, ['USD', 'EUR'], true)
                || strtoupper((string) $foreignAccount->currency) !== $currency) {
                return response()->json(['error' => 'Foreign account not found'], 404);
            }
        
            $user = $foreignAccount->user;

            // --- Idempotency: never process the same provider credit twice ---
            $providerRef = 'graph:deposit:' . $depositId;
            if ($providerRef && Transaction::where('paymentReference', $providerRef)->exists()) {
                return response()->json(['success' => true, 'message' => 'Already processed'], 200);
            }
        
            // --- Fetch applicable deposit fee ---
            // $feeRecord = DepositFee::where('currency', $currency)
            //     ->where('minimum_amount', '<=', $amountPaid)
            //     ->where('maximum_amount', '>=', $amountPaid)
            //     ->first();
            
            // Handle deposit fee with unlimited maximum amount consideration
            $feeRecord = DepositFee::where('currency', $currency)
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
                $feeRecord = DepositFee::where('currency', $currency)
                    ->where(function ($query) {
                        $query->where('maximum_amount', 'unlimited')
                              ->orWhereNull('maximum_amount');
                    })
                    ->orderBy('minimum_amount', 'desc')
                    ->first();
            }
        
            $fee = 0;
            if ($feeRecord) {
                if (($feeRecord->charge_type ?? 'percentage') === 'fixed') {
                    // Flat fee in this currency
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
        
            // --- Atomic, race-safe foreign-wallet credit (row-locked) ---
            $trx = strtoupper(Str::random(16));
            [$before, $after, $alreadyProcessed] = DB::transaction(function () use ($user, $foreignAccount, $currency, $amount_to_credit, $amountPaid, $providerRef, $trx, $fee, $description) {
                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                $lockedFA   = \App\Models\ForeignAccount::where('id', $foreignAccount->id)->lockForUpdate()->first();
                if (Transaction::where('paymentReference', $providerRef)->exists()) {
                    return [null, null, true];
                }

                if ($currency === 'USD') {
                    $b = (float) ($lockedUser->usd_balance ?? 0);
                    $a = $b + (float) $amount_to_credit;
                    $lockedUser->usd_balance = $a;
                } elseif ($currency === 'EUR') {
                    $b = (float) ($lockedUser->eur_balance ?? 0);
                    $a = $b + (float) $amount_to_credit;
                    $lockedUser->eur_balance = $a;
                } else {
                    $b = 0;
                    $a = (float) $amount_to_credit;
                }
                $lockedUser->save();

                if ($lockedFA) {
                    $lockedFA->balance = (float) $lockedFA->balance + (float) $amountPaid;
                    $lockedFA->save();
                }

                $lockedUser->transaction()->create([
                    'user_level_id' => $lockedUser->user_level_id,
                    'wallet_id' => '1',
                    'service' => 'System',
                    'service_type' => 'Wallet Funding',
                    'description' => $description,
                    'amount' => $amountPaid,
                    'real_price' => $amount_to_credit,
                    'charge' => $fee,
                    'phone_number' => $lockedUser->phone_number,
                    'type' => 1,
                    'initial_balance' => $b,
                    'final_balance' => $a,
                    'reference' => $trx,
                    'paymentReference' => $providerRef,
                    'status' => 'success',
                    'currency' => $currency,
                ]);

                return [$b, $a, false];
            });
            if ($alreadyProcessed) {
                return response()->json(['success' => true, 'message' => 'Already processed']);
            }

            // Keep in-memory models consistent for the code that follows.
            if ($currency === 'USD') { $user->usd_balance = $after; }
            elseif ($currency === 'EUR') { $user->eur_balance = $after; }
            $foreignAccount->balance = (float) $foreignAccount->balance + (float) $amountPaid;
        
            // --- Create transaction record ---
            // --- Send Email Notification ---
            if (getSiteSettings("email_notify") == "Yes") {
                $actionWord = "CREDIT";
                $formattedDate = now()->format('d-M-Y h:i A');
                $formattedAmount = number_format($amountPaid, 2);
                $formattedNet = number_format($amount_to_credit, 2);
                $formattedFee = number_format($fee, 2);
                $formattedBalance = number_format($after, 2);
        
                $accountName = strtoupper($foreignAccount->account_name ?? (($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? '')));
                $accountNumber = $foreignAccount->account_number ?? 'N/A';
                $maskedAccount = strlen($accountNumber) > 4
                    ? substr($accountNumber, 0, 1) . 'XX..' . substr($accountNumber, -2)
                    : $accountNumber;
        
                $message = "
                    <p>This is to inform you that a transaction has occurred on your <strong>Dupay {$currency} Account</strong> with the details below:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Account Name</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$accountName}</strong></td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$actionWord} ALERT</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Amount Received</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$currency} {$formattedAmount}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$currency}</td>
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
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$trx}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>{$currency} {$formattedBalance}</strong></td>
                        </tr>
                    </table>
                    <br>
                    <p>Thank you for choosing <strong>Dupay</strong>. We appreciate your trust in our services.</p>
                ";
        
                $subject = "{$currency} Account Credit Notification";
                $this->sendMail($user->email, $user->profile->first_name ?? '', $subject, $message);
            }
        }

        else {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        return response()->json(['success' => true]);
    }
}
