<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Fintech\BankAccount;
use App\Models\Telecom\VirtualAccount;

function getSafeHavenAccessToken()
{
    $baseUrl = rtrim(config('services.safehaven.base_url'), '/');
    $clientId = config('services.safehaven.client_id');
    $assertion = config('services.safehaven.client_assertion');
    if (!$clientId || !$assertion) {
        Log::error('SafeHaven credentials are not configured');
        return null;
    }
    // Cache the token for 55 minutes (SafeHaven tokens last 60 min)
    return \Cache::remember('safehaven_access_token_' . hash('sha256', $clientId . $assertion), 35 * 60, function () use ($baseUrl, $clientId, $assertion) {
        try {
            $response = Http::timeout(15)->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/oauth2/token', [
                "grant_type" => "client_credentials",
                "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
                "client_id" => $clientId,
                "client_assertion" => $assertion,
            ]);

            if ($response->successful()) {
                \Log::info('SafeHaven Token fetched and cached');
                return $response->json('access_token');
            }

            \Log::error('SafeHaven Token Error', [
                'status' => $response->status(),
                'provider_code' => $response->json('statusCode'),
            ]);
        } catch (\Exception $e) {
            \Log::error('SafeHaven Token Exception', ['error' => $e->getMessage()]);
        }

        return null;
    });
}

function createSafeHavenSubaccount(array $data)
{
    $token = getSafeHavenAccessToken();

    if (!$token) {
        return ['success' => false, 'message' => 'Token generation failed.'];
    }

    $url = rtrim(config('services.safehaven.base_url'), '/') . '/accounts/v2/subaccount';
    $phone = preg_replace('/\D+/', '', (string) $data['phoneNumber']);
    if (preg_match('/^0[789]\d{9}$/', $phone)) {
        $phone = '+234' . substr($phone, 1);
    } elseif (preg_match('/^234[789]\d{9}$/', $phone)) {
        $phone = '+' . $phone;
    } else {
        return ['success' => false, 'message' => 'A valid Nigerian phone number is required.'];
    }
    $clientId = config('services.safehaven.client_id');
    $sweepAccount = config('services.safehaven.sweep_account');
    if (!$clientId || !$sweepAccount) {
        return ['success' => false, 'message' => 'SafeHaven configuration is incomplete.'];
    }

    $payload = [
        "phoneNumber"        => $phone,
        "externalReference"  => (string) $data['externalReference'],
        "emailAddress"       => $data['emailAddress'],
        "autoSweep"          => true,
        "autoSweepDetails" => [
        "schedule"      => "Instant",
            "accountNumber" => $sweepAccount,
        ],
        "identityNumber"     => $data['identityNumber'],
        "identityType"       => $data['identityType'],
        "dateOfBirth"        => $data['dob'],
        "booleanMatch"       => true,
    ];
    if (!empty($data['identityId'])) $payload['identityId'] = $data['identityId'];
    if (!empty($data['otp'])) $payload['otp'] = $data['otp'];

    try {
        $response = Http::withHeaders([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'ClientID'      => $clientId,
        ])->connectTimeout(10)->timeout(45)->post($url, $payload);

        // Log any failed response for traceability
        if (!$response->successful()) {
            Log::error('SafeHaven Subaccount Failed', [
                'status' => $response->status(),
                'provider_code' => $response->json('statusCode'),
                'message' => $response->json('message'),
            ]);

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Request failed.',
                'status_code' => $response->status(),
                'provider_code' => $response->json('statusCode'),
            ];
        }

        $result = $response->json();

        // Handle unexpected API structure
        if (!isset($result['statusCode']) || $result['statusCode'] != 200 || empty($result['data'])) {
            Log::warning('SafeHaven Subaccount Unexpected Response', [
                'provider_code' => $result['statusCode'] ?? null,
                'message' => $result['message'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Unexpected API response.',
                'provider_code' => $result['statusCode'] ?? null,
            ];
        }

        $account = $result['data'];
        if (empty($account['accountNumber'])) {
            return ['success' => false, 'message' => 'SafeHaven did not return an account number.', 'outcome_unknown' => true];
        }

        // Save locally
        $virtualAccount = VirtualAccount::firstOrCreate([
            'account_reference' => $account['externalReference'] ?? $data['externalReference'],
        ], [
            'user_id'                => $data['user_id'],
            'bank_name'              => 'SafeHaven MFB',
            'account_name'           => $account['accountName'] ?? null,
            'account_number'         => $account['accountNumber'] ?? null,
            'virtual_account_type_id'=> 3,
            'gateway'                => 'safehaven',
            'status'                 => 1,
            'raw_data'               => json_encode($result),
        ]);

        if (!$virtualAccount || !$virtualAccount->exists) {
            Log::error('SafeHaven: Virtual account not saved locally', [
                'user_id' => $data['user_id'],
                'account' => $account
            ]);

            return [
                'success' => false,
                'message' => 'Account created on SafeHaven but failed to save locally.',
                'account' => $account
            ];
        }

        return [
            'success' => true,
            'message' => $result['message'] ?? 'Subaccount created successfully.',
            'account' => [
                'account_name'        => $account['accountName'] ?? null,
                'account_number'      => $account['accountNumber'] ?? null,
                'externalReference'   => $account['externalReference'] ?? null,
                'status'              => $account['status'] ?? null,
            ]
        ];

    } catch (ConnectionException $e) {
        Log::warning('SafeHaven subaccount outcome unknown after connection failure', [
            'user_id' => $data['user_id'],
            'external_reference' => (string) $data['externalReference'],
        ]);
        return [
            'success' => false,
            'outcome_unknown' => true,
            'message' => 'SafeHaven did not respond. Check whether the account was created before retrying.',
        ];
    } catch (\Exception $e) {
        Log::error('SafeHaven Subaccount Exception', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

function initiateSafeHavenIdentity(array $data): array
{
    $token = getSafeHavenAccessToken();
    $clientId = config('services.safehaven.client_id');
    if (!$token || !$clientId) return ['success' => false, 'message' => 'SafeHaven configuration is incomplete.'];
    $phone = preg_replace('/\D+/', '', (string) ($data['phoneNumber'] ?? ''));
    if (preg_match('/^0[789]\d{9}$/', $phone)) $phone = '+234' . substr($phone, 1);
    elseif (preg_match('/^234[789]\d{9}$/', $phone)) $phone = '+' . $phone;
    $response = Http::withToken($token)->withHeaders(['ClientID' => $clientId])->timeout(30)->post(rtrim(config('services.safehaven.base_url'), '/') . '/identity/v2', [
        'type' => strtoupper($data['type']), 'number' => (string) $data['number'], 'debitAccountNumber' => config('services.safehaven.sweep_account'), 'async' => false,
    ]);
    $json = $response->json();
    $id = data_get($json, 'data._id') ?: data_get($json, 'data.id') ?: $json['_id'] ?? null;
    return $id ? ['success' => true, 'identity_id' => $id] : ['success' => false, 'message' => $json['message'] ?? 'SafeHaven could not find this identity record.'];
}

function validateSafeHavenIdentity(string $identityId, string $type, string $otp): array
{
    $token = getSafeHavenAccessToken();
    $clientId = config('services.safehaven.client_id');
    if (!$token || !$clientId) return ['success' => false, 'message' => 'SafeHaven configuration is incomplete.'];
    $response = Http::withToken($token)->withHeaders(['ClientID' => $clientId])->timeout(30)->post(rtrim(config('services.safehaven.base_url'), '/') . '/identity/v2/validate', [
        'identityId' => $identityId, 'type' => strtoupper($type), 'otp' => $otp,
    ]);
    return $response->successful() && (int) ($response->json('statusCode') ?? 200) < 300
        ? ['success' => true]
        : ['success' => false, 'message' => $response->json('message') ?? 'Incorrect verification code.'];
}

function verifySafeHavenTransfer(string $sessionId): array
{
    $client = new Client([
        'base_uri' => 'https://api.safehavenmfb.com/',
        'timeout'  => 15.0,
    ]);

    try {
        $response = $client->request('POST', '/transfers/status', [
            'json' => ['sessionId' => $sessionId],
            'headers' => [
                'ClientID' => "23b0250d6b2c7f4c1caf1b2cc5de5f08",
                'accept' => 'application/json',
                'authorization' => 'Bearer ' . getSafeHavenAccessToken(),
                'content-type' => 'application/json',
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        if (!isset($responseData['statusCode'], $responseData['responseCode'], $responseData['data'])) {
            return [
                'verified' => false,
                'message' => 'Invalid response structure from SafeHaven API',
                'data' => $responseData
            ];
        }

        $isVerified = (
            $responseData['statusCode'] === 200 &&
            $responseData['responseCode'] === '00' &&
            ($responseData['data']['status'] ?? null) === 'Completed' &&
            ($responseData['data']['isReversed'] ?? true) === false
        );

        return [
            'verified' => $isVerified,
            'message' => $responseData['message'] ?? 'Transfer verification response',
            'data' => $responseData['data']
        ];

    } catch (RequestException $e) {
        $errorResponse = $e->getResponse() 
            ? json_decode($e->getResponse()->getBody()->getContents(), true)
            : null;

        return [
            'verified' => false,
            'message' => $errorResponse['message'] ?? $e->getMessage(),
            'data' => $errorResponse['data'] ?? null
        ];
    } catch (Exception $e) {
        return [
            'verified' => false,
            'message' => $e->getMessage(),
            'data' => null
        ];
    }
}

function fetchSafeHavenBanks(): array
{
    $client = new Client([
        'timeout' => 30,
    ]);

    try {

        $response = $client->request('GET', 'https://api.safehavenmfb.com/transfers/banks', [
            'headers' => [
                'ClientID'      => "23b0250d6b2c7f4c1caf1b2cc5de5f08",
                'Authorization' => 'Bearer ' . getSafeHavenAccessToken(),
                'Accept'        => 'application/json',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        // Check API response
        if (
            isset($result['statusCode']) &&
            $result['statusCode'] == 200 &&
            isset($result['responseCode']) &&
            $result['responseCode'] === '00'
        ) {

            $banks = collect($result['data'])->map(function ($bank) {
                return [
                    'name'         => $bank['name'] ?? null,
                    'bank_code'    => $bank['bankCode'] ?? null,
                    'routing_key'  => $bank['routingKey'] ?? null,
                    'category_id'  => $bank['categoryId'] ?? null,
                    'logo'         => $bank['logoImage'] ?? null,
                    'nuban_code'   => $bank['nubanCode'] ?? null,
                ];
            })->values()->toArray();

            return [
                'success' => true,
                'data'    => $banks,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Unable to fetch banks',
            'data'    => [],
        ];

    } catch (RequestException $e) {

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];

    } catch (\Exception $e) {

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];
    }
}

function safeHavenNameEnquiry(string $bankCode, string $accountNumber): array
{
    $client = new Client([
        'timeout' => 30,
    ]);

    $url = 'https://api.safehavenmfb.com/transfers/name-enquiry';

    try {

        $payload = [
            'bankCode'      => $bankCode,
            'accountNumber' => $accountNumber,
        ];

        $response = $client->request('POST', $url, [
            'headers' => [
                'ClientID'      => "23b0250d6b2c7f4c1caf1b2cc5de5f08",
                'Authorization' => 'Bearer ' . getSafeHavenAccessToken(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],

            'json' => $payload,
        ]);

        $responseBody = $response->getBody()->getContents();

        // Log successful API response
        Log::info('SafeHaven Name Enquiry Success', [
            'url'      => $url,
            'payload'  => $payload,
            'response' => json_decode($responseBody, true),
        ]);

        $json = json_decode($responseBody);

        // Successful response
        if (
            isset($json->statusCode) &&
            $json->statusCode == 200 &&
            isset($json->responseCode) &&
            $json->responseCode === "00"
        ) {

            $datas = [
                'session_id'     => $json->data->sessionId ?? null,
                'bank_code'      => $json->data->bankCode ?? null,
                'account_number' => $json->data->accountNumber ?? null,
                'account_name'   => $json->data->accountName ?? null,
                // 'token' => getSafeHavenAccessToken(),
            ];

            return [
                'success' => true,
                'message' => $json->data->responseMessage ?? 'Account verified successfully',
                'data'    => $datas,
            ];
        }

        // Log failed API response
        Log::warning('SafeHaven Name Enquiry Failed', [
            'url'      => $url,
            'payload'  => $payload,
            'response' => json_decode($responseBody, true),
        ]);

        return [
            'success' => false,
            'message' => $json->message ?? 'Name enquiry failed',
            'data'    => [],
        ];

    } catch (RequestException $e) {

        Log::error('SafeHaven Name Enquiry Request Exception', [
            'url'       => $url,
            'payload'   => [
                'bankCode'      => $bankCode,
                'accountNumber' => $accountNumber,
            ],
            'error'     => $e->getMessage(),
            'response'  => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null,
            'trace'     => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];

    } catch (\Exception $e) {

        Log::error('SafeHaven Name Enquiry General Exception', [
            'url'     => $url,
            'payload' => [
                'bankCode'      => $bankCode,
                'accountNumber' => $accountNumber,
            ],
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];
    }
}

function safeHavenBankTransfer(array $payload): array
{
    $client = new Client([
        'timeout' => 60,
    ]);

    $url = 'https://api.safehavenmfb.com/transfers';

    try {

        $requestPayload = [
            'nameEnquiryReference'     => $payload['name_enquiry_reference'],
            'debitAccountNumber'       => $payload['debit_account_number'],
            'beneficiaryBankCode'      => $payload['beneficiary_bank_code'],
            'beneficiaryAccountNumber' => $payload['beneficiary_account_number'],
            'amount'                   => $payload['amount'],
            'saveBeneficiary'          => $payload['save_beneficiary'] ?? false,
            'narration'                => $payload['narration'],
            'paymentReference'         => $payload['payment_reference'],
        ];

        Log::info('SafeHaven Transfer Request', [
            'url'     => $url,
            'payload' => $requestPayload,
        ]);

        $response = $client->request('POST', $url, [
            'headers' => [
                'ClientID'      => "23b0250d6b2c7f4c1caf1b2cc5de5f08",
                'Authorization' => 'Bearer ' . getSafeHavenAccessToken(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],

            'json' => $requestPayload,
        ]);

        $body = $response->getBody()->getContents();

        Log::info('SafeHaven Transfer Raw Response', [
            'url'      => $url,
            'payload'  => $requestPayload,
            'response' => json_decode($body, true),
        ]);

        $json = json_decode($body);

        $resCode = (string) ($json->responseCode ?? $json->data->responseCode ?? '');
        $statusCode = (int) ($json->statusCode ?? $response->getStatusCode());
        $providerStatus = strtolower(trim((string) ($json->data->status ?? $json->status ?? '')));
        $successfulStatuses = ['success', 'successful', 'completed', 'complete', 'approved', 'created'];
        $hasSuccessMarker = $resCode === '00'
            || in_array($providerStatus, $successfulStatuses, true)
            || isset($json->data->approvedAt);

        $extractedSessionId = (string) ($json->data->sessionId ?? $json->sessionId ?? '');

        // Successful transfer
        if (
            $statusCode >= 200 &&
            $statusCode < 300 &&
            $extractedSessionId !== '' &&
            $hasSuccessMarker
        ) {

            $data = [
                'sessionId'                  => $json->data->sessionId ?? null,
                'name_enquiry_reference'      => $json->data->nameEnquiryReference ?? null,
                'payment_reference'           => $json->data->paymentReference ?? null,
                'status'                      => $json->data->status ?? null,
                'amount'                      => $json->data->amount ?? null,
                'narration'                   => $json->data->narration ?? null,

                'beneficiary_bank_code'       => $json->data->destinationInstitutionCode ?? null,
                'beneficiary_account_name'    => $json->data->creditAccountName ?? null,
                'beneficiary_account_number'  => $json->data->creditAccountNumber ?? null,

                'debit_account_name'          => $json->data->debitAccountName ?? null,
                'debit_account_number'        => $json->data->debitAccountNumber ?? null,

                'response_code'               => $resCode,
                'response_message'            => $json->data->responseMessage ?? $json->message ?? 'Transfer completed successfully',

                'created_at'                  => $json->data->createdAt ?? null,
                'approved_at'                 => $json->data->approvedAt ?? null,
            ];

            Log::info('SafeHaven Transfer Success', [
                'url'      => $url,
                'payload'  => $requestPayload,
                'response' => $data,
            ]);

            return [
                'success' => true,
                'message' => $json->data->responseMessage ?? 'Transfer completed successfully',
                'data'    => $data,
            ];
        }

        Log::error('SafeHaven Transfer Failed', [
            'url'      => $url,
            'payload'  => $requestPayload,
            'response' => json_decode($body, true),
        ]);

        return [
            'success' => false,
            'message' => $json->message ?? 'Transfer failed',
            'data'    => [
                'sessionId' => $extractedSessionId !== '' ? $extractedSessionId : null,
            ],
        ];

    } catch (RequestException $e) {

        Log::error('SafeHaven Transfer RequestException', [
            'url'      => $url,
            'payload'  => $requestPayload,
            'message'  => $e->getMessage(),
            'response' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null,
            'trace'    => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];

    } catch (\Exception $e) {

        Log::error('SafeHaven Transfer General Exception', [
            'url'      => $url,
            'payload'  => $requestPayload,
            'message'  => $e->getMessage(),
            'trace'    => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];
    }
}

