<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\ForeignAccount;
use Carbon\Carbon;

function CreateGraphPerson(User $user, $data): array
{
    $url = "https://api.useoval.com/person";
    $token = env('GRAPH_API_KEY');
    $maxRetries = 3;
    $retryDelay = 1;

    $client = new Client(['timeout' => 30]);
    $payload = [];

    try {
        // ✅ Validate document files exist
       // Strip leading /storage/ prefix for Storage::disk('public') compatibility
       
       
$doc1Path = ltrim(preg_replace('#^/storage/#', '', $data->document), '/');
$doc2Path = ltrim(preg_replace('#^/storage/#', '', $data->document2), '/');

// ✅ Validate document files exist
if (!Storage::disk('public')->exists($doc1Path)) {
    Log::error('Document 1 not found', ['path' => $doc1Path]);
    return [
        'status' => false,
        'message' => 'Document 1 not found in storage',
        'error' => 'Missing document: ' . $doc1Path
    ];
}

if (!Storage::disk('public')->exists($doc2Path)) {
    Log::error('Document 2 not found', ['path' => $doc2Path]);
    return [
        'status' => false,
        'message' => 'Document 2 not found in storage',
        'error' => 'Missing document: ' . $doc2Path
    ];
}

// ✅ Build document arrays
$document1 = [
    "type" => $data->document_type ?? "passport",
    "url" => asset('storage/' . $doc1Path),
];

        if (!empty($data->document_issue_date)) {
            $document1["issue_date"] = $data->document_issue_date;
        }

        if (!empty($data->document_expiry_date)) {
            $document1["expiry_date"] = $data->document_expiry_date;
        }

        $document2 = [
            "type" => $data->document2_type ?? "bank_statement",
            // "url" => asset('storage/' . $data->document2),
            "url" => asset('storage/' . $doc2Path),
        ];

        if (!empty($data->document2_issue_date)) {
            $document2["issue_date"] = $data->document2_issue_date;
        }

        if (!empty($data->document2_expiry_date)) {
            $document2["expiry_date"] = $data->document2_expiry_date;
        }

        // ✅ Build payload
        $payload = [
            "id_level" => "primary",
            // id_type must be the uploaded ID DOCUMENT type (national_id /
            // passport / etc.) — the same value used in the documents array,
            // which Oval accepts. It was previously set from the wallet KYC
            // method, which for BVN users sent id_type "bvn" and Oval rejected
            // it with "invalid ID type provided" (BVN is not a document type; it
            // goes in bank_id_number below, which we already send).
            "id_type" => strtolower($data->document_type ?? "passport"),
            "kyc_level" => "basic",
            "address" => [
                "line1" => $data->address_line1,
                "line2" => $data->address_line2,
                "city" => $data->city,
                "state" => $data->state,
                "country" => "NG",
                "postal_code" => $data->postal_code,
            ],
            "background_information" => [
                "employment_status" => $data->employment_status,
                "occupation" => $data->occupation,
                "primary_purpose" => $data->primary_purpose,
                "source_of_funds" => $data->source_of_funds,
                "expected_monthly_inflow" => (int) $data->expected_monthly_inflow,
            ],
            "name_first" => $user->walletVerification->first_name,
            "name_last" => $user->walletVerification->last_name,
            "name_other" => $user->walletVerification->other_names,
            "phone" => '234' . ltrim($user->phone_number, '0'),
            "email" => $user->email,
            "dob" => Carbon::parse($user->walletVerification->date_of_birth)->format('Y-m-d'),
            "id_number" => $user->walletVerification->bvn,
            "id_country" => strtoupper($user->walletVerification->country ?? "NG"),
            "bank_id_number" => $data->bvn ?? "12345678901",
            "documents" => [$document1, $document2],
        ];

        // ✅ Retry mechanism
        $response = null;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer {$token}",
                    ],
                    'json' => $payload,
                ]);
                break;
                
            } catch (\GuzzleHttp\Exception\ServerException $e) {
                $lastException = $e;
                if ($attempt === $maxRetries) throw $e;
                Log::warning("Graph API attempt {$attempt} failed, retrying...");
                sleep($retryDelay);
                
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $lastException = $e;
                if ($attempt === $maxRetries) throw $e;
                Log::warning("Graph API connection attempt {$attempt} failed, retrying...");
                sleep($retryDelay);
                
            } catch (\Exception $e) {
                throw $e;
            }
        }

        if (!$response) {
            throw $lastException ?? new \Exception('Failed to get response after retries');
        }

        $result = json_decode($response->getBody()->getContents(), true);

        Log::info('Graph API Response', [
            'url' => $url,
            'response' => $result,
        ]);

        // ✅ CORRECTED: Check API success based on actual response structure
        $isSuccess = isset($result['status']) && $result['status'] === 'success';
        
        // ✅ Extract the person ID from the data object
        $personId = $isSuccess && isset($result['data']['id']) 
            ? $result['data']['id'] 
            : null;
        
        // ✅ Verify documents were uploaded (check for id_upload field or similar)
        $documentsUploaded = false;
        if ($isSuccess && isset($result['data']['id_upload'])) {
            // At least one document was uploaded
            $documentsUploaded = true;
            Log::info('Documents uploaded successfully', [
                'id_upload' => $result['data']['id_upload']
            ]);
        }

        // ✅ CRITICAL: Save graph_person_id IMMEDIATELY on success
        $graphPersonSaved = false;
        if ($isSuccess && $personId) {
            try {
                $user->graph_person_id = $personId;
                $graphPersonSaved = $user->save();
                
                if ($graphPersonSaved) {
                    Log::info('Graph person ID saved successfully', [
                        'user_id' => $user->id,
                        'graph_person_id' => $personId
                    ]);
                } else {
                    Log::error('Failed to save graph_person_id to database', [
                        'user_id' => $user->id,
                        'graph_person_id' => $personId
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Database error saving graph_person_id', [
                    'user_id' => $user->id,
                    'graph_person_id' => $personId,
                    'error' => $e->getMessage()
                ]);
                $graphPersonSaved = false;
            }
        }

        return [
            'status' => $isSuccess,
            'message' => $result['message'] ?? ($isSuccess ? 'Person created successfully' : 'Person creation failed'),
            'data' => $result['data'] ?? null,
            'graph_person_id' => $personId,
            'graph_person_saved' => $graphPersonSaved,
            'documents_uploaded' => $documentsUploaded,
        ];

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $errorBody = $e->getResponse()->getBody()->getContents();
        $decodedError = json_decode($errorBody, true);

        Log::error('Graph API Client Error', [
            'url' => $url,
            'payload' => $payload,
            'response' => $decodedError,
            'exception_message' => $e->getMessage(),
        ]);

        return [
            'status' => false,
            'message' => $decodedError['message'] ?? 'Validation failed',
            'error' => $decodedError,
            'graph_person_saved' => false,
        ];

    } catch (\Exception $e) {
        Log::error('Graph API General Error', [
            'url' => $url,
            'payload' => $payload,
            'exception_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => 'Unexpected error: ' . $e->getMessage(),
            'error' => $e->getMessage(),
            'graph_person_saved' => false,
        ];
    }
}

// function CreateGraphPerson(User $user, $data): array
// {
//     $url = "https://api.useoval.com/person";
//     $token = env('GRAPH_API_KEY');

//     $client = new Client(['timeout' => 30]);

//     try {
//         $document1 = [
//             "type" => $data->document_type ?? "passport",
//             "url" => asset('storage/' . $data->document),
//         ];

//         if (!empty($data->document_issue_date)) {
//             $document1["issue_date"] = $data->document_issue_date;
//         }

//         if (!empty($data->document_expiry_date)) {
//             $document1["expiry_date"] = $data->document_expiry_date;
//         }

//         $document2 = [
//             "type" => $data->document2_type ?? "bank_statement",
//             "url" => asset('storage/' . $data->document2),
//         ];

//         if (!empty($data->document2_issue_date)) {
//             $document2["issue_date"] = $data->document2_issue_date;
//         }

//         if (!empty($data->document2_expiry_date)) {
//             $document2["expiry_date"] = $data->document2_expiry_date;
//         }

//         $payload = [
//             "id_level" => "primary",
//             "id_type" => strtolower($user->walletVerification->method),
//             "kyc_level" => "basic",
//             "address" => [
//                 "line1" => $data->address_line1,
//                 "line2" => $data->address_line2,
//                 "city" => $data->city,
//                 "state" => $data->state,
//                 "country" => "NG",
//                 "postal_code" => $data->postal_code,
//             ],
//             "background_information" => [
//                 "employment_status" => $data->employment_status,
//                 "occupation" => $data->occupation,
//                 "primary_purpose" => $data->primary_purpose,
//                 "source_of_funds" => $data->source_of_funds,
//                 "expected_monthly_inflow" => (int)$data->expected_monthly_inflow,
//             ],
//             "name_first" => $user->walletVerification->first_name,
//             "name_last" => $user->walletVerification->last_name,
//             "name_other" => $user->walletVerification->other_names,
//             "phone" => '234' . ltrim($user->phone_number, '0'),
//             "email" => $user->email,
//             "dob" => Carbon::parse($user->walletVerification->date_of_birth)->format('Y-m-d'),
//             "id_number" => $user->walletVerification->bvn,
//             "id_country" => strtoupper($user->walletVerification->country ?? "NG"),
//             "bank_id_number" => $data->bvn ?? "12345678901",
//             "documents" => [$document1, $document2],
//         ];

//         $response = $client->post($url, [
//             'headers' => [
//                 'Accept' => 'application/json',
//                 'Content-Type' => 'application/json',
//                 'Authorization' => "Bearer {$token}",
//             ],
//             'json' => $payload,
//         ]);

//         $result = json_decode($response->getBody()->getContents(), true);

//         if (isset($result['status']) && $result['status'] === 'success' && isset($result['data']['id'])) {
//             $user->graph_person_id = $result['data']['id'];
//             $user->save();
//         }

//         return [
//             'status' => true,
//             'message' => $result['message'] ?? 'Person created successfully',
//             'data' => $result['data'] ?? null,
//         ];

//     } catch (\GuzzleHttp\Exception\ClientException $e) {
//         $errorBody = $e->getResponse()->getBody()->getContents();
//         $decodedError = json_decode($errorBody, true);

//         Log::error('Graph API Client Error', [
//             'url' => $url,
//             'payload' => $payload,
//             'response' => $decodedError,
//             'exception_message' => $e->getMessage(),
//         ]);

//         return [
//             'status' => false,
//             'message' => 'Validation failed',
//             'error' => $decodedError,
//         ];

//     } catch (\Exception $e) {
//         Log::error('Graph API General Error', [
//             'url' => $url,
//             'payload' => $payload,
//             'exception_message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//         ]);

//         return [
//             'status' => false,
//             'message' => 'Unexpected error',
//             'error' => $e->getMessage(),
//         ];
//     }
// }

function CreateGraphBankAccount(User $user, $currency): array
{
    $url = "https://api.useoval.com/bank_account";
    $token = env('GRAPH_API_KEY');

    $client = new Client([
        'timeout' => 30,
    ]);

    try {
        $payload = [
            "currency" => $currency ?? "USD",
            "autosweep_enabled" => true,
            "whitelist_enabled" => false,
            "person_id" => $user->graph_person_id,
            "label" => "{$user->profile->username}'s Oval Account",
        ];

        $response = $client->post($url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ],
            'json' => $payload
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return [
            'status'  => ($result['status'] ?? 'error') === 'success',
            'message' => $result['message'] ?? 'No message returned',
            'data'    => $result['data'] ?? null,
        ];

    } catch (\GuzzleHttp\Exception\ClientException $e) {

        $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);

        Log::error('Graph Bank Account Error', [
            'error' => $errorBody
        ]);

        return [
            'status'  => false,
            'message' => $errorBody['message'] ?? 'Client error occurred',
            'data'    => null,
        ];

    } catch (\Exception $e) {

        Log::error('Graph API Exception: ' . $e->getMessage());

        return [
            'status'  => false,
            'message' => $e->getMessage(),
            'data'    => null,
        ];
    }
}

function FetchGraphBankAccount($bankAccountId)
{
    $client = new Client();

    try {
        $response = $client->request('GET', "https://api.useoval.com/bank_account/{$bankAccountId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'Accept'        => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        $data = $body['data'] ?? [];

        return [
            'status'  => true,
            'message' => $body['message'] ?? 'Bank account retrieved successfully',
            'data'    => [
                'id'                     => $data['id'] ?? null,
                'holder_id'              => $data['holder_id'] ?? null,
                'holder_type'            => $data['holder_type'] ?? null,
                'funding_track'          => $data['funding_track'] ?? null,
                'supported_jurisdictions'=> $data['supported_jurisdictions'] ?? [],
                'label'                  => $data['label'] ?? null,
                'account_name'           => $data['account_name'] ?? null,
                'account_number'         => $data['account_number'] ?? null,
                'routing_number'         => $data['routing_number'] ?? null,
                'iban'                   => $data['iban'] ?? null,
                'swift_code'             => $data['swift_code'] ?? null,
                'check_number'           => $data['check_number'] ?? null,
                'bank_name'              => $data['bank_name'] ?? null,
                'bank_code'              => $data['bank_code'] ?? null,
                'branch_code'            => $data['branch_code'] ?? null,
                'bank_address'           => $data['bank_address'] ?? [],
                'beneficiary_address'    => $data['beneficiary_address'] ?? [],
                'memo_information'       => $data['memo_information'] ?? [],
                'routing_options'        => $data['routing_options'] ?? [],
                'currency'               => $data['currency'] ?? null,
                'currency_settlement'    => $data['currency_settlement'] ?? null,
                'balance'                => $data['balance'] ?? 0,
                'credit_pending'         => $data['credit_pending'] ?? 0,
                'debit_pending'          => $data['debit_pending'] ?? 0,
                'block_expiry'           => $data['block_expiry'] ?? null,
                'autosweep_enabled'      => $data['autosweep_enabled'] ?? false,
                'whitelist_enabled'      => $data['whitelist_enabled'] ?? false,
                'type'                   => $data['type'] ?? null,
                'kind'                   => $data['kind'] ?? null,
                'status'                 => $data['status'] ?? null,
                'status_description'     => $data['status_description'] ?? null,
                'is_deleted'             => $data['is_deleted'] ?? false,
                'created_at'             => $data['created_at'] ?? null,
                'updated_at'             => $data['updated_at'] ?? null,
                'settlement_config'      => $data['settlement_config'] ?? [],
            ],
        ];

    } catch (RequestException $e) {
        $errorMessage = 'Request failed';
        if ($e->hasResponse()) {
            $responseBody = json_decode($e->getResponse()->getBody(), true);
            $errorMessage = $responseBody['message'] ?? $errorMessage;
        } else {
            $errorMessage = $e->getMessage();
        }

        // Optional: log the full error payload for debugging
        \Log::error('FetchGraphBankAccount error', [
            'bankAccountId' => $bankAccountId,
            'error'         => $errorMessage,
            'response'      => $e->hasResponse() ? (string) $e->getResponse()->getBody() : null,
        ]);

        return [
            'status'  => false,
            'message' => $errorMessage,
            'data'    => null,
        ];
    }
}

function UpdateBankAccountStatus(string $bankAccountId, string $status)
{
    $client = new \GuzzleHttp\Client();

    try {
        $response = $client->request('PATCH', "https://api.useoval.com/bank_account/{$bankAccountId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
            'body' => json_encode([
                'status' => $status,
            ]),
        ]);

        $body = json_decode($response->getBody(), true);

        // Optionally update DB if you store the bank account info
        // \DB::table('bank_accounts')
        //     ->where('oval_id', $bankAccountId)
        //     ->update([
        //         'status' => $body['data']['status'] ?? $status,
        //         'updated_at' => now(),
        //     ]);

        return [
            'status' => true,
            'message' => $body['message'] ?? 'Bank account updated successfully',
            'data' => $body['data'] ?? [],
        ];
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return [
            'status' => false,
            'message' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody(), true)['message'] ?? 'Request failed'
                : $e->getMessage(),
            'data' => null,
        ];
    }
}

function GraphWhitelistBankAccount(string $bankAccountId, string $accountNumber, string $bankId)
{
    $client = new \GuzzleHttp\Client();

    try {
        $response = $client->request('POST', "https://api.useoval.com/bank_account/{$bankAccountId}/whitelist", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ],
            'body' => json_encode([
                'account_number' => $accountNumber,
                'bank_id' => $bankId,
            ]),
        ]);

        $body = json_decode($response->getBody(), true);

        // Optional: store whitelist data in your database
        // \DB::table('bank_account_whitelists')->updateOrInsert(
        //     ['whitelist_id' => $body['data']['id']],
        //     [
        //         'bank_account_id' => $bankAccountId,
        //         'bank_id' => $body['data']['bank_id'] ?? $bankId,
        //         'account_number' => $body['data']['account_number'] ?? $accountNumber,
        //         'account_name' => $body['data']['account_name'] ?? null,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]
        // );

        return [
            'status' => true,
            'message' => $body['message'] ?? 'Bank account added to whitelist',
            'data' => $body['data'] ?? [],
        ];
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return [
            'status' => false,
            'message' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody(), true)['message'] ?? 'Request failed'
                : $e->getMessage(),
            'data' => null,
        ];
    }
}

function GraphListWhitelistedAccounts(string $bankAccountId, int $page = 1, int $perPage = 20)
{
    $client = new \GuzzleHttp\Client();

    try {
        $url = "https://api.useoval.com/bank_account/{$bankAccountId}/whitelist?page={$page}&per_page={$perPage}";

        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'accept' => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);

        // Optional: Store or sync whitelist records locally
        // if (!empty($body['data'])) {
        //     foreach ($body['data'] as $item) {
        //         \DB::table('bank_account_whitelists')->updateOrInsert(
        //             ['whitelist_id' => $item['id']],
        //             [
        //                 'bank_account_id' => $bankAccountId,
        //                 'bank_id' => $item['bank_id'] ?? null,
        //                 'account_number' => $item['account_number'] ?? null,
        //                 'account_name' => $item['account_name'] ?? null,
        //                 'created_at' => now(),
        //                 'updated_at' => now(),
        //             ]
        //         );
        //     }
        // }

        return [
            'status' => true,
            'message' => $body['message'] ?? 'Whitelist accounts retrieved successfully',
            'data' => $body['data'] ?? [],
        ];
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return [
            'status' => false,
            'message' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody(), true)['message'] ?? 'Request failed'
                : $e->getMessage(),
            'data' => null,
        ];
    }
}

function RemoveGraphWhitelistedAccount(string $accountId, string $whitelistId): array
{
    $url = "https://api.useoval.com/bank_account/{$accountId}/whitelist/{$whitelistId}";

    try {
        $client = new Client();
        $response = $client->delete($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'Accept'        => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return [
            'status'  => true,
            'message' => $body['message'] ?? 'Whitelist account removed successfully',
            'data'    => $body['data'] ?? null,
        ];
    } catch (RequestException $e) {
        $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
        return [
            'status'  => false,
            'message' => 'Failed to remove whitelist account',
            'error'   => $error,
        ];
    }
}

function clearWhitelistedAccounts(string $accountId): array
{
    $url = "https://api.useoval.com/bank_account/{$accountId}/whitelist";

    try {
        $client = new Client();
        $response = $client->delete($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'Accept'        => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);

        return [
            'status'  => true,
            'message' => $body['message'] ?? 'Whitelist accounts cleared successfully',
            'data'    => $body['data'] ?? null,
        ];
    } catch (RequestException $e) {
        $error = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

        return [
            'status'  => false,
            'message' => 'Failed to clear whitelist accounts',
            'error'   => $error,
        ];
    }
}

function fetchGraphDeposit(string $depositId): array
{
    try {
        $client = new Client();

        $response = $client->get("https://api.useoval.com/deposit/{$depositId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $result['status'] === 'success',
            'message' => $result['message'] ?? 'No message provided',
            'data' => $result['data'] ?? [],
        ];
    } catch (\Exception $e) {
        Log::error('Graph Fetch Deposit Error: ' . $e->getMessage());

        return [
            'status' => false,
            'message' => 'Failed to fetch deposit details.',
            'error' => $e->getMessage(),
        ];
    }
}

function listGraphDeposits(int $page = 1, int $perPage = 20): array
{
    try {
        $client = new Client();

        $response = $client->get("https://api.useoval.com/deposit?page={$page}&per_page={$perPage}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('GRAPH_API_KEY'),
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => $result['status'] === 'success',
            'message' => $result['message'] ?? 'No message returned',
            'data' => $result['data'] ?? [],
            'meta' => $result['meta'] ?? [],
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Failed to list deposits.',
            'error' => $e->getMessage(),
        ];
    }
}