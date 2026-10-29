<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ForeignAccountRequest;
use App\Models\ForeignAccount;
use Carbon\Carbon;
use App\Models\User;
use App\TraitsFolder\MailTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForeignAccountController extends Controller
{
    use MailTrait;
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'currency'                => 'required|string|in:GPB,USD,EUR',
            'bvn'                     => 'required|digits:11',
            'address_line1'           => 'required|string|max:255',
            'address_line2'           => 'nullable|string|max:255',
            'city'                    => 'required|string|max:100',
            'state'                   => 'required|string|max:100',
            'country'                 => 'required|string|max:100',
            'postal_code'             => 'required|string|max:20',
        
            'employment_status'       => 'required|string|in:employed,self_employed,unemployed,student,retired',
            'occupation'              => 'required|string|max:100',
        
            'primary_purpose'         => 'required|string|in:business,personal,salary,freelance',
        
            'source_of_funds'         => 'required|string|in:salary,savings,business,freelance,investment,government_benefits,pension',
        
            'expected_monthly_inflow' => 'required|numeric|min:0',
        
            'document'                => 'required|string',
            'document_type'           => 'required|string|in:passport,national_id,drivers_licence,residence_permit,voters_card,bank_statement,utility_bill,incorporation_certificate,registration_certificate',
            'document_issue_date'     => 'nullable|date_format:Y-m-d',
            'document_expiry_date'    => 'nullable|date_format:Y-m-d',
        
            'document2'               => 'required|string',
            'document2_type'          => 'required|string|in:passport,national_id,drivers_licence,residence_permit,voters_card,bank_statement,utility_bill,incorporation_certificate,registration_certificate',
            'document2_issue_date'    => 'nullable|date_format:Y-m-d',
            'document2_expiry_date'   => 'nullable|date_format:Y-m-d',
        ]);
    
        $user = Auth::user();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
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
        
        if (!$user->hasVerifyWallet) {
            return response(['success' => false, 'message' => "Kindly verify your account"]);
        }
    
        $checkPending = ForeignAccountRequest::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();
    
        if ($checkPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request. Please wait for approval.',
            ], 400);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Base64 File Saver
        |--------------------------------------------------------------------------
        */
        $saveBase64 = function (?string $base64, string $folder, string $prefix): ?string {
    
            if (!$base64) return null;
    
            $cleanBase64 = preg_replace('#^data:[^;]+;base64,#', '', $base64);
            $binary = base64_decode($cleanBase64, true);
    
            if ($binary === false) return null;
    
            $extension = 'jpg';
            if (str_contains($base64, 'image/png')) $extension = 'png';
            if (str_contains($base64, 'application/pdf')) $extension = 'pdf';
    
            $filename = "{$prefix}_" . time() . '_' . uniqid() . '.' . $extension;
            $path = "$folder/$filename";
    
            Storage::disk('public')->put($path, $binary);
    
            return Storage::url($path);
        };
    
        // Save Documents
        $validated['document']  = $saveBase64($request->document,  'foreign_accounts/documents',  'passport');
        $validated['document2'] = $saveBase64($request->document2, 'foreign_accounts/documents2', 'utility');
        $validated['document3'] = $saveBase64($request->document3, 'foreign_accounts/documents3', 'bank');
    
        $validated['user_id'] = Auth::id();
    
        try {
    
            ForeignAccountRequest::create($validated);
    
            ForeignAccount::create([
                'user_id'  => Auth::id(),
                'status'   => 'pending',
                'currency' => $request->currency,
                'active'   => false,
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | Send Email Notification (NEW)
            |--------------------------------------------------------------------------
            */
            try {
    
                $message = '
                    <p>Your foreign account request has been received successfully.</p>
    
                    <p>Our compliance team is currently reviewing your submission.</p>
    
                    <p>You will be notified once your account is approved and activated.</p>
    
                    <p>If you did not make this request, please contact support immediately.</p>
                ';
    
                $this->sendMail(
                    $user->email,
                    $user->profile->first_name ?? 'Customer',
                    'Foreign Account Request Submitted',
                    $message
                );
    
            } catch (\Throwable $th) {
                \Log::error('Foreign account email failed', [
                    'user_id' => $user->id,
                    'error'   => $th->getMessage()
                ]);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Your foreign account request has been submitted and is under review.',
            ], 201);
    
        } catch (\Exception $e) {
    
            \Log::error('Foreign account request failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while submitting your request. Please try again later.',
            ], 500);
        }
    }
    
    public function createAccount(Request $request)
    {
        $user = auth()->user();
    
        $validatedData = Validator::make($request->all(), [
            'currency' => 'required|string',
        ]);
    
        if ($validatedData->fails()) {
            $error = implode(', ', $validatedData->errors()->all());
            return response()->json([
                'success' => false,
                'message' => $error,
            ]);
        }
    
        //check transaction pin
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        // Create foreign account
        $response = CreateGraphBankAccount($user, $request->currency);
    
        if ($response['status'] === true) {
            return response()->json([
                'success' => true,
                'message' => "Your foreign account request has been submitted and is under review.",
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create account. Please try again later.',
                'error' => $response['message'] ?? NULL,
            ]);
        }
    }
    
    public function getForeignAccount($currency)
    {
        $currency = strtoupper($currency);
        $user = Auth::user();
    
        $foreignAccount = ForeignAccount::where('user_id', $user->id)
            ->where('currency', $currency) 
            ->first();
    
        if (!$foreignAccount) {
            return response()->json([
                'success' => true,
                'message' => "No {$currency} foreign account found for this user.",
                'data' => null,
            ],200);
        }
        
        
         return response()->json([
            'success'  => true,
            'message' => "{$currency} foreign account fetched and updated successfully.",
            'data'    => $foreignAccount,
        ]);
    
        $latestData = FetchGraphBankAccount($foreignAccount->account_id);
    
        if (!$latestData || empty($latestData['status']) || !$latestData['status'] || empty($latestData['data'])) {
            return response()->json([
                'success' => false,
                'message' => $latestData['message'] ?? "Unable to fetch updated {$currency} account details.",
            ], 500);
        }
    
        $data = $latestData['data'];
    
        $foreignAccount->update([
            'account_number' => $data['account_number'] ?? $foreignAccount->account_number,
            'account_name'   => $data['account_name'] ?? $foreignAccount->account_name,
            'bank_name'      => $data['bank_name'] ?? $foreignAccount->bank_name,
            'bank_code'      => $data['bank_code'] ?? $foreignAccount->bank_code,
            'routing_number' => $data['routing_number'] ?? $foreignAccount->routing_number,
            'swift_code' => $data['swift_code'] ?? NULL,
            'iban' => $data['iban'] ?? NULL,
            'bank_address' => $data['bank_address'] ?? NULL,
            'beneficiary_address' => $data['beneficiary_address'] ?? NULL,
            'balance'        => $data['balance'] ?? $foreignAccount->balance,
        ]);
    
        return response()->json([
            'success'  => true,
            'message' => "{$currency} foreign account fetched and updated successfully.",
            'data'    => $foreignAccount,
        ]);
    }
    
    public function whitelistAccount(Request $request, $id)
    {
        $validated = $request->validate([
            'account_number' => 'required|string',
            'bank_id' => 'required|string',
        ]);
        
        $user = Auth::user();
        
        //check transaction pin
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        try {
            $bank = ForeignAccount::findOrFail($id);
    
            if (empty($bank->account_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing account ID for this record.',
                ], 400);
            }
    
            $response = GraphWhitelistBankAccount(
                $bank->account_id,
                $validated['account_number'],
                $validated['bank_id']
            );
    
            if (isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account whitelisted successfully.',
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to whitelist account.',
            ], 400);
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Foreign account not found.',
            ], 404);
    
        } catch (\Throwable $e) {
            \Log::error('Whitelist Account Error: '.$e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while whitelisting the account.',
            ], 500);
        }
    }
    
    public function listWhitelistAccount(Request $request, $id)
    {
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1|max:100',
        ]);
        
        $user = Auth::user();
        
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        try {
            $bank = ForeignAccount::findOrFail($id);
    
            if (empty($bank->account_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing account ID for this record.',
                ], 400);
            }
    
            $response = GraphListWhitelistedAccounts(
                $bank->account_id,
                $validated['page'],
                $validated['per_page']
            );
    
            if (isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Whitelisted accounts fetched successfully.',
                    'data' => $response['data'] ?? [],
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to fetch whitelisted accounts.',
                'data' => $response['data'] ?? [],
            ], 400);
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Foreign account not found.',
            ], 404);
    
        } catch (\Throwable $e) {
            \Log::error('List Whitelist Account Error: '.$e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while fetching whitelisted accounts.',
            ], 500);
        }
    }
    
    public function removeWhitelistAccount($id, $item_id)
    {
        $user = Auth::user();
        
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
        
        try {
            $bank = ForeignAccount::findOrFail($id);
    
            if (empty($bank->account_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing account ID for this record.',
                ], 400);
            }
    
            if (empty($item_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing whitelist item ID.',
                ], 400);
            }
    
            $response = RemoveGraphWhitelistedAccount($bank->account_id, $item_id);
    
            if (isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account successfully removed from whitelist.',
                    'data' => $response['data'] ?? null,
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Failed to remove whitelisted account.',
                'data' => $response['data'] ?? null,
            ], 400);
    
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Foreign account not found.',
            ], 404);
    
        } catch (\Throwable $e) {
            \Log::error('Remove Whitelist Account Error: '.$e->getMessage(), [
                'foreign_account_id' => $id,
                'item_id' => $item_id,
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while removing whitelisted account.',
            ], 500);
        }
    }
    
    public function convert(Request $request)
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|in:NGN,USD,EUR',
            'to_currency'   => 'required|string|in:NGN,USD,EUR|different:from_currency',
            'amount'        => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }

        $from   = strtoupper($validated['from_currency']);
        $to     = strtoupper($validated['to_currency']);
        $amount = (float) $validated['amount'];

        $fromField = match ($from) {
            'NGN' => 'balance',
            'USD' => 'usd_balance',
            'EUR' => 'eur_balance',
        };

        $toField = match ($to) {
            'NGN' => 'balance',
            'USD' => 'usd_balance',
            'EUR' => 'eur_balance',
        };

        // The fee is now charged in the SOURCE currency and is taken out of the
        // amount before conversion, so the user still only needs `amount` of the
        // source currency available. A single source-balance check is enough.
        if ($user->$fromField < $amount) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient {$from} balance.",
            ], 400);
        }

        // applyFee = TRUE -> helper charges the percentage fee (+ cap) in the SOURCE
        // currency and converts only the remainder.
        $conversion = convertCurrency($from, $to, $amount, true);

        if (empty($conversion) || ($conversion['status'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => $conversion['message'] ?? "Conversion from {$from} to {$to} failed.",
            ], 400);
        }

        $conversion = $conversion['data'];

        $exchangeRate  = $conversion['exchange_rate'];
        $grossConverted = round($amount * $exchangeRate, 2);   // amount*rate before fee, for reference
        $feeAmount     = $conversion['fee_amount'];            // fee in the FROM (source) currency
        $finalAmount   = $conversion['converted_amount'];      // already NET of the fee, in TO currency
        $totalDebit    = $amount;                              // user gives up `amount` of source; fee is inside it

        $init_from_balance = $user->$fromField;
        $init_to_balance   = $user->$toField;

        $user->$fromField -= $totalDebit;
        $user->$toField   += $finalAmount;
        $user->save();

        $trx = strtoupper(Str::random(12));

        $des_famount = number_format($finalAmount, 2);
        // NGN->USD/EUR rates are tiny multipliers (1/1510 = 0.00066225), so 2dp
        // rendered them as "at rate 0.00" in the alert and the receipt. Show the
        // stored precision for sub-cent rates, trimming trailing zeros, the same
        // way the fee below handles sub-cent amounts.
        $des_eamount = ($exchangeRate != 0 && abs($exchangeRate) < 0.01)
            ? rtrim(rtrim(number_format($exchangeRate, 8, '.', ''), '0'), '.')
            : number_format($exchangeRate, 2);
        // Show sub-cent fees (small USD/EUR amounts) instead of rounding to 0.00
        $des_fee     = number_format($feeAmount, ($feeAmount != 0 && abs($feeAmount) < 0.01) ? 4 : 2);

        $description = "Converted {$amount} {$from} to {$des_famount} {$to} at rate {$des_eamount} (fee {$des_fee} {$from})";
        $walletId = 1;

        // 💰 Debit transaction (from currency) — the fee is charged in the SOURCE
        // currency, so the profit (charge) is booked on THIS row.
        $user->transaction()->create([
            'user_level_id'    => $user->user_level_id,
            'wallet_id'        => $walletId,
            'service'          => "System",
            'service_type'     => "Currency Conversion",
            'description'      => $description,
            'amount'           => $totalDebit,
            'charge'           => round($feeAmount, 4), // fee taken, in FROM currency (profit); 4dp keeps sub-cent USD/EUR fees
            'currency'         => $from,
            'phone_number'     => $user->phone_number,
            'type'             => "0", // debit
            'initial_balance'  => $init_from_balance,
            'final_balance'    => $user->$fromField,
            'reference'        => $trx,
            'status'           => "success",
        ]);

        // 💰 Credit transaction (to currency) — fee is now charged on the debit
        // (source) side, so no charge is booked here.
        $user->transaction()->create([
            'user_level_id'    => $user->user_level_id,
            'wallet_id'        => $walletId,
            'service'          => "System",
            'service_type'     => "Currency Conversion",
            'description'      => $description,
            'amount'           => $finalAmount,
            'real_price'       => $grossConverted,   // gross before fee (optional, mirrors deposit rows)
            'charge'           => 0,                 // fee is booked on the FROM-currency debit row
            'currency'         => $to,
            'phone_number'     => $user->phone_number,
            'type'             => "1", // credit
            'initial_balance'  => $init_to_balance,
            'final_balance'    => $user->$toField,
            'reference'        => $trx,
            'status'           => "success",
        ]);

        if (getSiteSettings("email_notify") == "Yes") {
            $formattedDate      = now()->format('d-M-Y h:i A');
            $formattedAmount    = number_format($amount, 2);
            $formattedConverted = number_format($finalAmount, 2);
            $formattedFee       = number_format($feeAmount, ($feeAmount != 0 && abs($feeAmount) < 0.01) ? 4 : 2);   // fee shown in FROM (source) currency
            $formattedFromBalance = number_format($user->$fromField, 2);
            $formattedToBalance   = number_format($user->$toField, 2);
            $accountName = "{$user->first_name} {$user->last_name}";
            $emailTitle  = "Dupay Currency Conversion Notification";

            $message = "
                <p>This is to notify you that a currency conversion transaction has been successfully completed on your Dupay account. Please find the details below:</p>
                <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                        <td style='padding:8px; border:1px solid #ddd;'>Currency Conversion</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Amount Converted</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedAmount} {$from}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Conversion Fee</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedFee} {$from}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Converted To</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedConverted} {$to}</td>
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
                        <td style='padding:8px; border:1px solid #ddd;'>Available {$from} Balance</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedFromBalance}</td>
                    </tr>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Available {$to} Balance</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$formattedToBalance}</td>
                    </tr>
                </table>
                <br>
                <p>Thank you for using Dupay.</p>
            ";

            $this->sendMail(
                $user->email,
                $user->profile->first_name,
                $emailTitle,
                $message
            );

            $deviceToken = $user->fcmToken;
            $title = "Dupay Currency Conversion";
            $body  = "Your currency conversion was successful. Your wallet balances have been updated.";
            $data = [
                "type"         => "conversion",
                "amount"       => $amount,
                "from"         => $from,
                "to"           => $to,
                "final_amount" => $finalAmount,
                "reference"    => $trx,
            ];
            sendFcmNotification($deviceToken, $title, $body, $data);
        }

        return response()->json([
            'success' => true,
            'message' => "Conversion successful from {$from} to {$to}.",
            'data' => [
                'from_currency'         => $from,
                'to_currency'           => $to,
                'exchange_rate'         => $exchangeRate,
                'fee_amount'            => round($feeAmount, 4),   // in FROM (source) currency (4dp keeps sub-cent fees)
                'fee_currency'          => $from,                 // currency the fee is charged in
                'original_amount'       => round($amount, 2),
                'gross_converted'       => $grossConverted,        // before fee
                'converted_amount'      => round($finalAmount, 2), // net of fee
                'final_amount_received' => round($finalAmount, 2),
                'total_deducted'        => round($totalDebit, 2),  // FROM currency
                'balances' => [
                    'NGN' => round($user->balance, 2),
                    'USD' => round($user->usd_balance, 2),
                    'EUR' => round($user->eur_balance, 2),
                ],
            ],
        ]);
    }
}