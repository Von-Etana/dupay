<?php

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Str;
use App\Models\CardTopup;
use App\Models\CardFreezeLog;
use App\Models\CardUnfreezeLog;
use App\Models\CardTerminateLog;
use App\Models\Fintech\VirtualCard;

function createCustomer($user)
{
    $url = 'https://sandbox.payscribe.ng/api/v1/customers/create';
    $apiKey = env('PAYSCRIBE_KEY_PUBLIC');

    $firstName = $user->profile->first_name ?? null;
    $lastName = $user->profile->last_name ?? null;
    $email = $user->email ?? null;
    $country = 'NG';
    $phone = preg_replace('/^0/', '234', $user->phone_number);

    // Quick local validation
    if (!$firstName || !$lastName || !$phone || !$email) {
        return [
            'success' => false,
            'message' => 'Missing required user fields (first name, last name, phone, or email).',
        ];
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'email'      => $email,
            'country'    => $country,
        ]);

        if ($response->successful() && $response->json('status') === true) {
            $customerId = $response->json('message.details.customer_id');

            $user->payscribe_customer_id = $customerId;
            $user->save();

            return [
                'success' => true,
                'message' => 'Customer created successfully.',
                'data' => ['customer_id' => $customerId],
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('description') ?? 'Customer creation failed.',
            'response' => $response->json(),
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception occurred: ' . $e->getMessage(),
        ];
    }
}

function upgradeToTier1($user, $dob, $address, $identificationType, $identificationNumber, $photoUrl)
{
    $url = 'https://sandbox.payscribe.ng/api/v1/customers/create/tier1';
    $apiKey = env('PAYSCRIBE_KEY_PUBLIC');

    $customerId = $user->payscribe_customer_id;

    // Quick local validation
    if (!$customerId || !$dob || !$address || !$identificationType || !$identificationNumber || !$photoUrl) {
        return [
            'success' => false,
            'message' => 'Missing required fields (customer ID, DOB, address, identification, or photo).',
        ];
    }

    try {
        $payload = [
            'customer_id' => $customerId,
            'dob' => $dob,
            'address' => [
                'street' => $address['street'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'country' => $address['country'] ?? 'NG',
                'postal_code' => $address['postal_code'] ?? '',
            ],
            'identification_type' => $identificationType,
            'identification_number' => $identificationNumber,
            'photo' => $photoUrl,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->patch($url, $payload);

        if ($response->successful() && $response->json('status') === true) {
            return [
                'success' => true,
                'message' => 'Customer upgraded to Tier 1 successfully.',
                'data' => $response->json('message.details'),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('description') ?? 'Tier 1 upgrade failed.',
            'response' => $response->json(),
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception occurred: ' . $e->getMessage(),
        ];
    }
}

function upgradeToTier2(string $customerId, string $nin, string $ninImageUrl): array
{
    $url = 'https://sandbox.payscribe.ng/api/v1//customers/create/tier2';

    $payload = [
        "customer_id" => $customerId,
        "identity" => [
            "type" => "NIN",
            "number" => $nin,
            "country" => "NG",
            "image" => $ninImageUrl,
        ],
    ];

    $headers = [
        "Authorization: Bearer " . env('PAYSCRIBE_SECRET_KEY'), // add this to .env
        "Content-Type: application/json",
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "PATCH",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        Log::error("Payscribe Tier 2 Upgrade Failed", ['error' => $error]);
        return [
            'status' => false,
            'message' => 'Curl error: ' . $error,
        ];
    }

    $body = json_decode($response, true);
    Log::info("Payscribe Tier 2 Upgrade Response", $body);

    return $body ?? ['status' => false, 'message' => 'Invalid response'];
}

function createFullCustomer(User $user, array $payload)
{
    try {
        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->post('https://sandbox.payscribe.ng/api/v1/customers/create/full', $payload);

        $body = $response->json();

        if ($response->successful() && $body['status'] === true) {
            $customerId = $body['message']['details']['customer_id'] ?? null;

            if ($customerId) {
                $user->payscribe_customer_id = $customerId;
                $user->save();
            }

            return [
                'status' => true,
                'message' => $body['description'],
                'data' => $body['message']['details']
            ];
        }

        return [
            'status' => false,
            'message' => $body['description'] ?? 'Failed to create customer.',
            'data' => $body
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Exception: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

function createBankAccount($customerId)
{
    $user = User::where('payscribe_customer_id', $customerId)->first();
    $existingAccount = BankAccount::where('user_id', $user->id)->first();

    if ($existingAccount) {
        return [
            'success' => false,
            'message' => 'A bank account has already been created for this user.',
        ];
    }

    $url = 'https://api.payscribe.ng/api/v1/collections/virtual-accounts/create';

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSCRIBE_KEY_PUBLIC'),
            'Content-Type' => 'application/json',
        ])->post($url, [
            'account_type' => 'static',
            'currency' => 'NGN',
            'customer_id' => $customerId,
            'bank' => ["9psb"],
        ]);
        
        $responseData = $response->json();

        if ($response->successful()  && $responseData['status'] === true) {
            $responseData = $response->json();
            

            if (isset($responseData['message']['details'])) { 
                $accountDetails = $responseData['message']['details']['account'][0] ?? null;

                if ($accountDetails) {
                    BankAccount::create([
                        'user_id' => $user->id,
                        // 'account_id' => $accountDetails['id'],
                        'account_number' => $accountDetails['account_number'],
                        'account_name' => $accountDetails['account_name'],
                        'bank_name' => $accountDetails['bank_name'],
                        // 'bank_code' => $accountDetails['bank_code'],
                        // 'currency' => $accountDetails['currency'],
                        // 'account_type' => $accountDetails['account_type'],
                    ]);

                    // $user->notify(new BankAccountNotification(
                    //     $customerDetails['name'],
                    //     $accountDetails['account_number']
                    // ));

                    return [
                        'success' => true,
                        'message' => 'Bank account created and saved successfully.',
                        'data' => $accountDetails,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Account or customer details are missing in the API response.',
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['description'] ?? 'Failed to create bank account due to API error.',
                'error_data' => $responseData,
            ];
        } else {
            Log::warning('Payscribe API call failed', [
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Payscribe API call failed. Please check the response.',
                'error_data' => $response->json(),
            ];
        }
    } catch (\Exception $e) {
        Log::error('Error creating bank account', [
            'customer_id' => $customerId,
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => 'An exception occurred: ' . $e->getMessage(),
        ];
    }
}

function createVirtualCard(string $customerId, string $currency, string $brand, float $amount): array
{
    $user = User::where('payscribe_customer_id', $customerId)->first();

    if (!$user) {
        return [
            'status' => false,
            'message' => 'User with provided Payscribe customer ID not found.',
        ];
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSCRIBE_KEY_PUBLIC'),
            'Accept'        => 'application/json',
        ])->post('https://sandbox.payscribe.ng/api/v1//cards/create', [
            'customer_id' => $customerId,
            'currency'    => strtoupper($currency),
            'brand'       => strtoupper($brand),
            'amount'      => $amount,
            'type'        => 'virtual',
            'ref'         => (string) Str::uuid(),
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? false)) {
            $details = $data['message']['details'];
            $card = $details['card'];
            $billing = $card['billing'] ?? [];
            $customer = $details['customer'] ?? [];

            $saved = VirtualCard::create([
                'user_id'           => $user->id,
                'trans_id'          => $details['trans_id'] ?? null,
                'card_id'           => $card['id'],
                'card_type'         => $card['card_type'],
                'currency'          => $card['currency'],
                'brand'             => $card['brand'],
                'name'              => $card['name'],
                'first_six'         => $card['first_six'],
                'last_four'         => $card['last_four'],
                'masked'            => $card['masked'],
                'number'            => $card['number'],
                'expiry'            => $card['expiry'],
                'ccv'               => $card['ccv'],
            ]);

            return [
                'status'  => true,
                'message' => 'Card created and saved successfully.',
                'card'    => $saved,
            ];
        }

        return [
            'status'  => false,
            'message' => 'Failed to create card.',
            'data'    => $data,
        ];
    } catch (\Exception $e) {
        return [
            'status'  => false,
            'message' => 'Exception: ' . $e->getMessage(),
        ];
    }
}

function topUpVirtualCard(string $cardId, float $amount)
{
    $ref = Str::uuid()->toString();

    try {
        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->patch("https://api.payscribe.ng/api/v1/cards/{$cardId}/topup", [
                'amount' => $amount,
                'ref' => $ref,
            ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? false)) {
            $details = $data['message']['details'] ?? [];

            // Save top-up to DB
            CardTopUp::create([
                'trans_id' => $details['trans_id'] ?? null,
                'card_id' => $details['card']['id'] ?? $cardId,
                'prev_balance' => $details['card']['prev_balance'] ?? 0,
                'balance' => $details['card']['balance'] ?? 0,
                'currency' => $details['currency'] ?? null,
                'ref_id' => $details['ref_id'] ?? $ref,
                'action' => $details['action'] ?? null,
                'transaction_time' => $details['created_at'] ?? now(),
            ]);

            return [
                'success' => true,
                'message' => 'Card top-up successful.',
                'ref' => $ref,
                'details' => $details,
            ];
        }

        Log::error('Payscribe card top-up failed.', [
            'card_id' => $cardId,
            'amount' => $amount,
            'response' => $data,
        ]);

        return [
            'success' => false,
            'message' => 'Card top-up failed.',
            'response' => $data,
        ];
    } catch (\Throwable $e) {
        Log::error('Exception during Payscribe card top-up.', [
            'card_id' => $cardId,
            'amount' => $amount,
            'error' => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'An error occurred during card top-up.',
        ];
    }
}

function freezeCard(string $cardId, string $ref): array
{
    try {
        $url = "https://api.payscribe.ng/api/v1/cards/{$cardId}/freeze";

        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->patch($url, [
                'ref' => $ref,
            ]);

        $body = $response->json();


        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $body,
            ];
        }

        Log::error('Payscribe Card Freeze Failed', [
            'card_id' => $cardId,
            'ref' => $ref,
            'response' => $body,
        ]);

        return [
            'status' => false,
            'message' => $body,
        ];
    } catch (\Throwable $e) {
        Log::error('Payscribe Card Freeze Exception', [
            'card_id' => $cardId,
            'ref' => $ref,
            'error' => $e->getMessage(),
        ]);

        return [
            'status' => false,
            'message' => 'An error occurred while freezing the card.',
        ];
    }
}

function unfreezeCard(string $cardId): array
{
    try {
        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->patch("https://api.payscribe.ng/api/v1//cards/{$cardId}/unfreeze");

        $body = $response->json();

        return [
            'status' => $body['status'] ?? false,
            'data' => $body
        ];
    } catch (\Throwable $th) {
        Log::error('Card Unfreeze Error', ['card_id' => $cardId, 'error' => $th->getMessage()]);
        return [
            'status' => false,
            'error' => $th->getMessage()
        ];
    }
}

function terminateCard(string $cardId, string $ref): array
{
    try {
        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->post("https://api.payscribe.ng/api/v1//cards/{$cardId}/terminate", [
                'ref' => $ref,
            ]);

        $body = $response->json();

        return [
            'status' => $body['status'] ?? false,
            'data' => $body
        ];
    } catch (\Throwable $th) {
        Log::error('Card Terminate Error', ['card_id' => $cardId, 'ref' => $ref, 'error' => $th->getMessage()]);
        return [
            'status' => false,
            'error' => $th->getMessage()
        ];
    }
}

function withdrawCard(string $cardId, float $amount, string $currency = 'USD', string $ref = null): array
{
    $ref = $ref ?? Str::uuid()->toString();

    try {
        $response = Http::withToken(env('PAYSCRIBE_KEY_PUBLIC'))
            ->patch("https://api.payscribe.ng/api/v1/cards/{$cardId}/withdraw", [
                'amount' => $amount,
                'ref' => $ref
            ]);

        $body = $response->json();
        $status = $body['status'] ?? false;

        return [
            'status' => $status,
            'ref' => $ref,
            'data' => $body,
        ];

    } catch (\Throwable $e) {
        Log::error('Card Withdrawal Failed: ' . $e->getMessage(), [
            'card_id' => $cardId,
            'amount' => $amount,
            'ref' => $ref
        ]);

        return [
            'status' => false,
            'ref' => $ref,
            'error' => $e->getMessage(),
        ];
    }
}

function getVirtualCardTransactions(string $cardId, string $startDate, string $endDate, int $pageSize = null, int $page = null)
{
    try {
        $token = env('PAYSCRIBE_KEY_PUBLIC');

        $url = "https://api.payscribe.ng/api/v1/cards/{$cardId}/transactions";

        $query = array_filter([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page_size' => $pageSize,
            'page' => $page,
        ]);

        $response = Http::withToken($token)->get($url, $query);

        if ($response->successful()) {
            return [
                'status' => true,
                'message' => 'Card transactions fetched successfully.',
                'data' => $response->json(),
            ];
        }

        return [
            'status' => false,
            'message' => $response->json()['message'] ?? 'Unable to fetch transactions.',
            'data' => null,
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Request failed: ' . $e->getMessage(),
            'data' => null,
        ];
    }
}

function getVirtualCardDetails(string $cardId)
{
    try {
        $token = env('PAYSCRIBE_KEY_PUBLIC');

        $url = "https://api.payscribe.ng/api/v1/cards/{$cardId}";

        $response = Http::withToken($token)->get($url);

        if ($response->successful()) {
            return [
                'status' => true,
                'message' => 'Card details fetched successfully.',
                'data' => $response->json(),
            ];
        }

        return [
            'status' => false,
            'message' => $response->json()['message'] ?? 'Unable to fetch card details.',
            'data' => null,
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Request failed: ' . $e->getMessage(),
            'data' => null,
        ];
    }
}

