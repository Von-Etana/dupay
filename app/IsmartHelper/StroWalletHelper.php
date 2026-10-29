<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;
use App\Models\VirtualCardUSD;
use App\Models\VirtualCardNGN;
use App\Models\User;

// ── Sandbox mode helper ──────────────────────────────────────────────────────
// Set STROWALLET_MODE=sandbox in .env to test without real money.
// Set STROWALLET_MODE=live (or remove it) to go live.
// Every function below reads this one value automatically.
function stroMode(): string
{
    return env('STROWALLET_MODE', 'live');
    
}

function stroModeParam(): array
{
    return stroMode() === 'sandbox' ? ['mode' => 'sandbox'] : [];
}

function createNairaCardUser(array $data, $user)
{
    $client = new Client();

    try {
        $url = 'https://strowallet.com/api/naira_carduser/';

        $response = $client->request('POST', $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
            'form_params' => [
                'public_key' => $data['public_key'],
                'firstname'  => $data['firstname'],
                'lastname'   => $data['lastname'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'nin'        => $data['nin'],
                'dob'        => $data['dob'],
                'name'       => $data['name'],
                'line1'      => $data['line1'],
                'city'       => $data['city'],
                'state'      => $data['state'],
                'provider'   => 'black',
            ] + stroModeParam(),
        ]);

        $body = json_decode($response->getBody(), true);

        // Pull the customer id, tolerating a couple of possible response shapes.
        $customerId = $body['data']['customer_id']
            ?? $body['data']['customerId']
            ?? $body['response']['customer_id']
            ?? $body['response']['customerId']
            ?? null;

        // ONLY treat as success when StroWallet says success AND a real id came back.
        // Previously we saved $body['data']['customer_id'] blindly — if it was
        // missing (e.g. KYC rejected), the column was set to NULL and we still
        // returned success, leaving the user unable to ever activate a card.
        if (isset($body['success']) && $body['success'] === true && !empty($customerId)) {
            $user->strowallet_customer_id = $customerId;
            $user->save();

            return [
                'status'  => true,
                'message' => $body['message'] ?? 'Customer created successfully.',
                'data'    => $body['data'] ?? $body,
            ];
        }

        // success=false, OR success=true with no id — never persist null.
        Log::warning('Strowallet naira_carduser: no customer_id returned', [
            'user_id'  => $user->id,
            'response' => $body,
        ]);

        return [
            'status'  => false,
            'message' => $body['message']
                ?? 'StroWallet did not return a customer ID. Please check the KYC details (NIN, date of birth, address) and try again.',
            'data'    => $body,
        ];

    } catch (\Exception $e) {
        Log::error('Strowallet Naira Card User Error: ' . $e->getMessage());

        return [
            'status'  => false,
            'message' => 'An error occurred while connecting to Strowallet.',
        ];
    }
}

function createNairaCardUsers(array $data)
{
    $client = new Client();

    try {
        $url = 'https://strowallet.com/api/naira_carduser/';

        $queryParams = http_build_query([
            'public_key' => $data['public_key'],
            'firstname'  => $data['firstname'],
            'lastname'   => $data['lastname'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'nin'        => $data['nin'],
            'dob'        => $data['dob'],
            'name'       => $data['name'],
            'line1'      => $data['line1'],
            'city'       => $data['city'],
            'state'      => $data['state'],
            'provider'   => 'black',
        ] + stroModeParam());

        $response = $client->request('POST', $url . '?' . $queryParams, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return [
            'status' => true,
            'data' => $body,
        ];

    } catch (\Exception $e) {
        Log::error('Create Naira Card User Error: ' . $e->getMessage());

        return [
            'status' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function newCustomer(array $payload, $user)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/create-user/', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params'     => array_merge($payload, stroModeParam()),
            // create-user is slow for BRAND-NEW customers (StroWallet fetches
            // the KYC images and runs verification). Give it room, but fail fast
            // on a dead host. http_errors=false so a 4xx/5xx is captured as a
            // body instead of throwing and losing the reason.
            'connect_timeout' => 15,
            // create-user hangs ("0 bytes") for new customers under ~90s; give
            // it the same headroom as create-card. It runs in the background
            // job, so no user is waiting on it.
            'timeout'         => 180,
            'http_errors'     => false,
        ]);

        $statusCode = $response->getStatusCode();
        $body       = (string) $response->getBody();
        $result     = json_decode($body, true);

        if (!empty($result['success']) && $result['success'] === true) {
            $customerId = $result['response']['customerId'] ?? null;

            if ($customerId) {
                $user->profile->strowallet_customer_id = $customerId;
                $user->profile->save();

                return [
                    'success' => true,
                    'message' => 'Customer created and ID saved',
                    'customer_id' => $customerId,
                    'data' => $result['response']
                ];
            }
        }

        // Log the HTTP status + RAW body (truncated) so an empty / HTML / 5xx
        // response is finally visible instead of a null.
        Log::warning('Strowallet bitvcard/create-user failed', [
            'user_id'     => $user->id ?? null,
            'status_code' => $statusCode,
            'raw_body'    => mb_substr($body, 0, 1000),
            'parsed'      => $result,
        ]);

        $providerMessage = $result['message']
            ?? (is_array($result['errors'] ?? null) ? implode(' ', $result['errors']) : ($result['errors'] ?? null))
            ?? ('Customer creation failed (HTTP ' . $statusCode . ').');

        return [
            'success'     => false,
            'message'     => $providerMessage,
            'response'    => $result,
            'status_code' => $statusCode,
            'raw_body'    => mb_substr($body, 0, 1000),
        ];
    } catch (ConnectException $e) {
        // No response at all — connect/read timeout.
        Log::error('Strowallet bitvcard/create-user timeout', [
            'user_id' => $user->id ?? null,
            'error'   => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'Timed out reaching the card partner. Please try again.',
            'error'   => $e->getMessage(),
        ];
    } catch (RequestException $e) {
        $errorBody = $e->hasResponse()
            ? (string) $e->getResponse()->getBody()
            : null;

        Log::error('Strowallet bitvcard/create-user exception', [
            'user_id' => $user->id ?? null,
            'error'   => $errorBody ?? $e->getMessage(),
        ]);

        return [
            'success'  => false,
            'message'  => 'Could not reach the card partner to set up your profile.',
            'error'    => $errorBody ?? $e->getMessage(),
            'raw_body' => $errorBody,
        ];
    } catch (\Exception $e) {
        Log::error('Strowallet bitvcard/create-user unexpected error', [
            'user_id' => $user->id ?? null,
            'error'   => $e->getMessage(),
        ]);

        return [
            'success' => false,
            'message' => 'Could not reach the card partner to set up your profile. Please try again.',
            'error'   => $e->getMessage(),
        ];
    }
}

function updateCardCustomer(array $params)
{
    $client = new Client();

    $url = 'https://strowallet.com/api/bitvcard/updateCardCustomer/';

    try {
        $response = $client->request('PUT', $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => $params,
        ]);

        $body = $response->getBody()->getContents();

        return [
            'status' => true,
            'message' => 'Customer updated successfully',
            'data' => json_decode($body, true)
        ];
    } catch (RequestException $e) {
        return [
            'status' => false,
            'message' => 'Failed to update customer',
            'error' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : $e->getMessage()
        ];
    }
}

function newCard(array $data)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/create-card/', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(array_merge($data, stroModeParam())),
            // Card creation is StroWallet's slowest call and regularly takes
            // more than 10s — the old 10s cap caused "cURL error 28: Operation
            // timed out". Match the 30s used by the other StroWallet calls,
            // with extra headroom for creation. connect_timeout still fails
            // fast if the host is unreachable.
            'connect_timeout' => 15,
            'timeout'         => 60,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);

        if ($statusCode !== 200 || empty($result['success']) || $result['success'] !== true) {
            return [
                'status' => false,
                'message' => $result['message'] ?? 'API error',
                'errors' => $result['errors'] ?? $result,
            ];
        }

        // Save card if user exists
        $responseData = $result['response'] ?? [];
        $email = $data['customerEmail'] ?? null;

        if ($email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                VirtualCardUSD::create([
                    'user_id' => $user->id,
                    'card_id' => $responseData['card_id'] ?? null,
                    'card_brand' => $responseData['card_brand'] ?? null,
                    'reference' => $responseData['reference'] ?? null,
                    'response_data' => json_encode($responseData),
                ]);
            } else {
                return [
                    'status' => false,
                    'message' => 'User not found',
                    'errors' => 'User not found',
                ];
            }
        }

        return [
            'status' => true,
            'message' => $result['message'] ?? 'Card created successfully',
            'data' => $responseData,
        ];
    } catch (RequestException $e) {
        $errorResponse = null;
        if ($e->hasResponse()) {
            $errorBody = $e->getResponse()->getBody()->getContents();
            $errorResponse = json_decode($errorBody, true);
        }

        return [
            'status' => false,
            'message' => 'Card creation failed',
            'errors' => $errorResponse ?: ['exception' => $e->getMessage()],
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => 'Unexpected error occurred',
            'errors' => ['exception' => $e->getMessage()],
        ];
    }
}

/**
 * Create a USD virtual card WITHOUT touching the database.
 *
 * This is the background-job version of newCard(): it just talks to StroWallet
 * and returns a normalised result so the caller (CreateUsdVirtualCardJob) can
 * decide what to persist. Crucially it distinguishes a *timeout / no-response*
 * (where StroWallet may still have created the card — must NOT refund/retry)
 * from a *definite decline* (safe to refund).
 *
 * Returns:
 *   ['status' => true,  'data' => [...]]                      // created
 *   ['status' => false, 'timeout' => true,  'message'=>...]   // inconclusive
 *   ['status' => false, 'timeout' => false, 'message'=>...]   // declined
 */
function createUsdCard(array $data)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/create-card/', [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(array_merge($data, stroModeParam())),
            // Runs in a queued job (no user waiting), so give StroWallet's slow
            // create-card real room to finish. connect_timeout still fails fast
            // if the host is unreachable.
            'connect_timeout' => 15,
            'timeout'         => 180,
        ]);

        $statusCode = $response->getStatusCode();
        $result     = json_decode($response->getBody()->getContents(), true);

        if ($statusCode !== 200 || empty($result['success']) || $result['success'] !== true) {
            return [
                'status'  => false,
                'timeout' => false, // StroWallet answered — a real decline
                'message' => $result['message'] ?? 'Card creation was declined by the card partner.',
                'errors'  => $result['errors'] ?? $result,
            ];
        }

        return [
            'status'  => true,
            'timeout' => false,
            'message' => $result['message'] ?? 'Card created successfully',
            'data'    => $result['response'] ?? [],
        ];
    } catch (ConnectException $e) {
        // cURL 28 etc. — no response received. The card MAY have been created.
        return [
            'status'  => false,
            'timeout' => true,
            'message' => 'Timed out waiting for the card partner.',
            'errors'  => ['exception' => $e->getMessage()],
        ];
    } catch (RequestException $e) {
        // If StroWallet returned a body it's a real decline; otherwise treat
        // it as inconclusive (network-level failure).
        $errorResponse = $e->hasResponse()
            ? json_decode($e->getResponse()->getBody()->getContents(), true)
            : null;

        return [
            'status'  => false,
            'timeout' => !$e->hasResponse(),
            'message' => 'Card creation request failed.',
            'errors'  => $errorResponse ?: ['exception' => $e->getMessage()],
        ];
    } catch (\Exception $e) {
        // Unknown — be conservative: treat as inconclusive so we never refund a
        // card that might exist.
        return [
            'status'  => false,
            'timeout' => true,
            'message' => 'Unexpected error during card creation.',
            'errors'  => ['exception' => $e->getMessage()],
        ];
    }
}

/**
 * Create a USD card via the NEW self-contained NFC endpoint.
 *
 * Unlike the old bitvcard flow, this needs NO create-user / cardholder step —
 * all KYC + amount go in ONE call, so the create-user hang is gone entirely.
 * Returns the same normalised shape as createUsdCard() so the job can reuse it:
 *   ['status'=>true,  'data'=>[...]]                    // accepted / in progress
 *   ['status'=>false, 'timeout'=>true,  'message'=>...] // inconclusive (no body)
 *   ['status'=>false, 'timeout'=>false, 'message'=>...] // declined
 */
function createNfcCard(array $data)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/create-nfc-card/', [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body'            => json_encode(array_merge($data, stroModeParam())),
            'connect_timeout' => 15,
            'timeout'         => 180,
            'http_errors'     => false,
        ]);

        $statusCode = $response->getStatusCode();
        $body       = (string) $response->getBody();
        $result     = json_decode($body, true);

        if ($statusCode === 200 && !empty($result['success']) && $result['success'] === true) {
            return [
                'status'  => true,
                'timeout' => false,
                'message' => $result['message'] ?? 'Card creation in progress',
                'data'    => $result['response'] ?? [],
            ];
        }

        // Got a body but not a success → real decline (log it so we can see why).
        if ($body !== '') {
            Log::warning('Strowallet create-nfc-card failed', [
                'status_code' => $statusCode,
                'raw_body'    => mb_substr($body, 0, 1000),
                'parsed'      => $result,
            ]);

            return [
                'status'      => false,
                'timeout'     => false,
                'message'     => $result['message']
                    ?? (is_array($result['errors'] ?? null) ? implode(' ', $result['errors']) : ($result['errors'] ?? null))
                    ?? ('Card creation was declined (HTTP ' . $statusCode . ').'),
                'errors'      => $result['errors'] ?? $result,
                'status_code' => $statusCode,
                'raw_body'    => mb_substr($body, 0, 1000),
            ];
        }

        // Empty body → inconclusive; the card may have been created, so DON'T refund.
        return [
            'status'      => false,
            'timeout'     => true,
            'message'     => 'Empty response from the card partner.',
            'status_code' => $statusCode,
        ];
    } catch (ConnectException $e) {
        return ['status' => false, 'timeout' => true, 'message' => 'Timed out reaching the card partner.', 'errors' => ['exception' => $e->getMessage()]];
    } catch (RequestException $e) {
        $errorResponse = $e->hasResponse() ? json_decode((string) $e->getResponse()->getBody(), true) : null;
        return ['status' => false, 'timeout' => !$e->hasResponse(), 'message' => 'Card creation request failed.', 'errors' => $errorResponse ?: ['exception' => $e->getMessage()]];
    } catch (\Exception $e) {
        return ['status' => false, 'timeout' => true, 'message' => 'Unexpected error during card creation.', 'errors' => ['exception' => $e->getMessage()]];
    }
}

function fundVCard(array $data)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/fund-card/', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(array_merge($data, stroModeParam())),
            'timeout' => 30,
        ]);

        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        Log::info('fundVCard SUCCESS', [
            'payload'  => $data,
            'response' => $json,
        ]);

        return $json;
    } catch (RequestException $e) {

        $body = $e->hasResponse()
            ? $e->getResponse()->getBody()->getContents()
            : null;

        $json = $body ? json_decode($body, true) : null;

        Log::error('fundVCard ERROR', [
            'payload'       => $data,
            'error_message' => $e->getMessage(),
            'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
            'response'      => $json ?: $body,
        ]);

        return $json ?: [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    } catch (\Throwable $e) {

        Log::critical('fundVCard CRITICAL ERROR', [
            'payload'   => $data,
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function getCardDetails(array $data)
{
    $client = new Client();

    try {
        // New NFC details endpoint — GET with query params (public_key, card_id,
        // mode). Replaces the old POST fetch-card-detail after the migration.
        $response = $client->get('https://strowallet.com/api/bitvcard/fetch-nfccard-detail', [
            'headers'         => ['Accept' => 'application/json'],
            'query'           => array_merge($data, stroModeParam()),
            'connect_timeout' => 15,
            'timeout'         => 45,
            'http_errors'     => false,
        ]);

        $body = (string) $response->getBody();
        $result = json_decode($body, true);

        if (!empty($result['success']) && $result['success'] === true) {
            $cardData = $result['response']['card_detail'] ?? null;

            if ($cardData) {
                $cardId = $cardData['card_id'] ?? null;
                $expiry = $cardData['expiry'] ?? null;

                if ($expiry && preg_match('/^(\d{2})\/(\d{4})$/', $expiry, $matches)) {
                    $expiry = $matches[1] . '/' . substr($matches[2], -2);
                }

                $card = VirtualCardUSD::where('card_id', $cardId)->first();

                if ($card) {
                    $updateData = [];

                    if (!empty($cardData['card_name']))   $updateData['card_name']   = $cardData['card_name'];
                    if (!empty($cardData['card_number'])) $updateData['card_number'] = $cardData['card_number'];
                    if (!empty($cardData['cvv']))         $updateData['cvv']         = $cardData['cvv'];
                    if (!empty($expiry))                  $updateData['expiry']      = $expiry;
                    if (!empty($cardData['card_type']))   $updateData['card_type']   = $cardData['card_type'];
                    if (!empty($cardData['card_brand']))  $updateData['card_brand']  = $cardData['card_brand'];

                    // NFC billing fields are address/city/state (not billing_street).
                    if (!empty($cardData['address']))     $updateData['billing_address'] = $cardData['address'];
                    if (!empty($cardData['city']))        $updateData['billing_city']    = $cardData['city'];
                    if (!empty($cardData['state']))       $updateData['billing_state']   = $cardData['state'];
                    if (!empty($cardData['postal']))      $updateData['billing_zip_code'] = $cardData['postal'];

                    $updateData['balance'] = $cardData['balance'] ?? $card->balance;

                    // NOTE: intentionally NOT overwriting local `status` here.
                    // StroWallet returns "Card creation in progress" while the
                    // card provisions; the local row status is owned by the job
                    // ('active' once accepted) so the card stays visible.

                    if (!empty($updateData)) {
                        $card->update($updateData);
                    }
                }
            }

            return [
                'status'  => true,
                'message' => $result['message'] ?? 'Card details retrieved successfully',
                'data'    => $cardData,
            ];
        }

        return [
            'status'  => false,
            'message' => 'Unable to retrieve card details',
            'error'   => $result,
        ];

    } catch (\Exception $e) {
        return [
            'status'  => false,
            'message' => 'Request failed',
            'error'   => $e->getMessage(),
        ];
    }
}

function getCardTransactions(array $data)
{
    $client = new Client();

    try {
        $response = $client->post('https://strowallet.com/api/bitvcard/card-transactions/', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(array_merge($data, stroModeParam()))
        ]);

        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);

        if (!empty($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
                'message' => $result['message'] ?? 'Card transactions retrieved successfully',
                'data' => $result['response']['card_transactions'] ?? []
            ];
        }

        return [
            'success' => false,
            'message' => 'Unable to fetch card transactions',
            'error' => $result
        ];
    } catch (RequestException $e) {
        return [
            'success' => false,
            'message' => 'Request failed',
            'error' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : $e->getMessage()
        ];
    }
}

function freezeOrUnfreezeCard(string $action, string $cardId, string $publicKey): array
{
    $client = new Client();
    $url = 'https://strowallet.com/api/bitvcard/action/status';

    try {
        $response = $client->request('POST', $url, [
            'query' => array_merge([
                'action' => $action,
                'card_id' => $cardId,
                'public_key' => $publicKey ?? env('STROWALLET_PUBLIC_KEY'),
            ], stroModeParam()),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        if (isset($body['status']) && $body['status'] === true) {
            return [
                'success' => true,
                'status_code' => $statusCode,
                'message' => $body['message'] ?? 'Action completed successfully.',
                'data' => $body['data'] ?? null,
            ];
        }

        // Treat anything else as an application-level error
        return [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $body['message'] ?? 'Unknown error occurred.',
            'data' => $body['data'] ?? null,
        ];

    } catch (RequestException $e) {
        $responseBody = null;
        $statusCode = $e->getCode() ?: 500;
        $message = 'Request failed.';

        if ($e->hasResponse()) {
            $responseBody = json_decode((string) $e->getResponse()->getBody(), true);
            $statusCode = $responseBody['statusCode'] ?? $statusCode;
            $message = $responseBody['message'] ?? $e->getMessage();
        } else {
            $message = $e->getMessage();
        }

        return [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $responseBody ?? null,
        ];

    } catch (ConnectException $e) {
        return [
            'success' => false,
            'status_code' => 503,
            'message' => 'Connection failed: ' . $e->getMessage(),
            'data' => null,
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'status_code' => 500,
            'message' => 'Unexpected error: ' . $e->getMessage(),
            'data' => null,
        ];
    }
}

function getCardFullHistory($cardId, $publicKey, $page = 1, $take = 50)
{
    try {
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', 'https://strowallet.com/api/apicard-transactions/', [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => array_merge([
                'card_id' => $cardId,
                'page' => $page,
                'take' => $take,
                'public_key' => $publicKey,
            ], stroModeParam()),
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['success'] === true && isset($body['response']['card_transactions'])) {
            return [
                'status' => true,
                'data' => $body['response']['card_transactions'],
            ];
        }

        return [
            'status' => false,
            'message' => $body['message'] ?? 'Unable to retrieve card transactions.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function withdrawFromCard(string $cardId, $amount, string $publicKey): array
{
    try {
        $client = new Client();

        $url = "https://strowallet.com/api/bitvcard/card_withdraw/?card_id={$cardId}&amount={$amount}&public_key={$publicKey}";

        $response = $client->post($url, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);

        // Normalize success/failure into consistent return structure
        return [
            'success'      => $body['success'] ?? false,
            'message'      => $body['message'] ?? 'No message returned',
            'reference'    => $body['reference'] ?? null,
            'status_code'  => $statusCode,
            'response'     => $body['response'] ?? null
        ];

    } catch (RequestException $e) {
        $errorResponse = null;
        $statusCode = null;

        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
            $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
        }

        return [
            'success'      => false,
            'message'      => $errorResponse['message'] ?? $e->getMessage(),
            'reference'    => $errorResponse['reference'] ?? null,
            'status_code'  => $statusCode,
            'response'     => $errorResponse ?? null
        ];
    } catch (\Exception $e) {
        return [
            'success'      => false,
            'message'      => $e->getMessage(),
            'reference'    => null,
            'status_code'  => null,
            'response'     => null
        ];
    }
}

function getCardWithdrawStatus(string $publicKey, string $reference): array
{
    try {
        $client = new Client();

        $url = "https://strowallet.com/api/bitvcard/getcard_withdrawstatus/?public_key={$publicKey}&reference={$reference}";

        $response = $client->get($url, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        return [
            'status' => true,
            'message' => $body['message'] ?? 'No message',
            'reference' => $body['reference'] ?? null,
            'withdraw_status' => $body['status'] ?? null,
            'amount' => $body['amount'] ?? null,
            'date' => $body['Date'] ?? null,
            'remark' => $body['remark'] ?? null,
        ];
    } catch (RequestException $e) {
        return [
            'status' => false,
            'message' => 'Request failed: ' . $e->getMessage(),
            'reference' => null,
            'withdraw_status' => null,
            'amount' => null,
            'date' => null,
            'remark' => null,
        ];
    }
}

function createVirtualNairaCard(int $userId, string $customerId, string $brand = 'Verve', string $type = 'virtual'): array
{
    try {
        $client = new Client();

        $url = "https://strowallet.com/api/naira_createcard/";
        $publicKey = env('STROWALLET_PUBLIC_KEY');

        $response = $client->request('POST', $url, [
            'query' => array_merge([
                'public_key' => $publicKey,
                'customerId' => $customerId,
                'type'       => $type,
                'brand'      => $brand,
                'provider'  => 'black',
            ], stroModeParam()),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!empty($result['success']) && $result['success'] === true) {
            // Save card details
            $card = VirtualCardNGN::create([
                'user_id'       => $userId,
                'card_id'       => $result['data']['card_id'] ?? null,
                'card_brand'    => $result['data']['brand'] ?? null,
                'status'        => $result['data']['status'] ?? 'inactive',
                'response_data' => json_encode($result['data']),
            ]);

            return [
                'status' => true,
                'message' => $result['message'] ?? 'Naira card created successfully.',
                'data'    => $card,
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? 'Failed to create Naira card.',
            'data'    => [],
        ];
    } catch (\Exception $e) {
        Log::error('Strowallet Create Card Error: ' . $e->getMessage());

        return [
            'status' => false,
            'message' => $e->getMessage(),
            'data'    => [],
        ];
    }
}

function getVirtualNairaCard(string $cardId): array
{
    try {
        $client = new Client();
        $url = "https://strowallet.com/api/naira_viewcard/";
        $publicKey = env('STROWALLET_PUBLIC_KEY');

        $response = $client->request('GET', $url, [
            'query' => array_merge([
                'public_key' => $publicKey,
                'card_id'    => $cardId,
            ], stroModeParam()),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!empty($result['success']) && $result['success'] === true) {
            $data = $result['data'] ?? [];
            $month = $data['expiry_month'] ?? null;
            $year  = $data['expiry_year'] ?? null;
            $expiry = $month && $year ? sprintf('%02d/%s', $month, substr($year, -2)) : null;

            $card = VirtualCardNGN::where('card_id', $cardId)->first();

            if ($card) {
                $updateData = [];

                if (empty($card->card_name) && !empty($data['customer_name'])) {
                    $updateData['card_name'] = $data['customer_name'];
                }

                if (empty($card->card_number) && !empty($data['card_number'])) {
                    $updateData['card_number'] = $data['card_number'];
                }

                if (empty($card->cvv) && !empty($data['cvv'])) {
                    $updateData['cvv'] = $data['cvv'];
                }

                if (empty($card->expiry) && !empty($expiry)) {
                    $updateData['expiry'] = $expiry;
                }

                if (!empty($data['card_type'])) {
                    $updateData['card_type'] = $data['card_type'];
                }

                if (!empty($data['billing_address']['line1'])) {
                    $updateData['billing_address'] = $data['billing_address']['line1'];
                }

                if (!empty($data['billing_address']['city'])) {
                    $updateData['billing_city'] = $data['billing_address']['city'];
                }

                if (!empty($data['billing_address']['state'])) {
                    $updateData['billing_state'] = $data['billing_address']['state'];
                }

                $updateData['status'] = $data['status'] ?? $card->status;

                if (!empty($updateData)) {
                    $card->update($updateData);
                }
            }

            return [
                'status' => true,
                'data'   => $data,
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? 'Failed to fetch card details.',
        ];

    } catch (\Exception $e) {
        Log::error('Strowallet View Card Error: ' . $e->getMessage(), [
            'card_id' => $cardId,
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => 'Error fetching virtual card details: ' . $e->getMessage(),
        ];
    }
}


function getVirtualNairaCardATMHistory(string $cardId): array
{
    try {
        $client = new Client();
        $url = "https://strowallet.com/api/naira_cardhistory/";
        $publicKey = env('STROWALLET_PUBLIC_KEY');

        $response = $client->request('GET', $url, [
            'query' => [
                'public_key' => $publicKey,
                'card_id'    => $cardId,
            ] + stroModeParam(),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!empty($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
                'data' => $result['data']['data'] ?? []
            ];
        }

        return [];
    } catch (\Exception $e) {
        Log::error('Strowallet ATM History Error: ' . $e->getMessage());
        return [];
    }
}

function updateVirtualCardStatus(string $cardId, string $status): array
{
    $client = new Client();
    $url = "https://strowallet.com/api/naira_ChangeStatus/";

    try {
        $response = $client->request('PUT', $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                'card_id'    => $cardId,
                'status'     => $status,
            ] + stroModeParam(),
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        return [
            'success' => $body['success'] ?? false,
            'message' => $body['message'] ?? 'Unknown response',
        ];
    } catch (RequestException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

function resetVirtualCardPin(string $cardId, string $newPin): array
{
    $client = new Client();
    $publicKey = env('STROWALLET_PUBLIC_KEY');

    $url = "https://strowallet.com/api/naira_resetPin/";

    try {
        $response = $client->request('PUT', $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'public_key' => $publicKey,
                'card_id'    => $cardId,
                'new_pin'    => $newPin,
            ] + stroModeParam(),
        ]);

        $data = json_decode($response->getBody(), true);

        return [
            'success' => $data['success'] ?? false,
            'message' => $data['message'] ?? 'Failed to reset PIN.',
            'data'    => $data
        ];
    } catch (RequestException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => []
        ];
    }
}

function enableSafeToken(string $publicKey, string $cardId): array
{
    $client = new Client();

    try {
        $url = "https://strowallet.com/api/naira_enable2Fa/";

        $response = $client->request('POST', $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
            'query' => [
                'public_key' => $publicKey,
                'card_id'    => $cardId,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        return [
            'success' => $body['success'] ?? true,
            'message' => $body['message'] ?? '2FA enrollment successful.',
        ];
    } catch (RequestException $e) {
        return [
            'success' => false,
            'message' => $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)['message'] ?? 'Request failed'
                : $e->getMessage(),
        ];
    }
}


// ── USD card PAN/CVV reveal (on demand) ──────────────────────────────────────
// StroWallet never returns the raw PAN/CVV. fetch-nfccard-detail returns last4
// plus short-lived URLs whose pages render the number/CVV via secure JavaScript
// (SecurePro vault) — viewable only inside a WebView. So the backend just hands
// the app FRESH reveal URLs each time; it cannot read the digits itself.

/**
 * @return array{success:bool, card_number_url:?string, cvv_url:?string, last4:?string, expiry:?string, message?:string}
 */
function getNfcCardSecrets(string $cardId): array
{
    $client    = new \GuzzleHttp\Client();
    $publicKey = env('STROWALLET_PUBLIC_KEY');

    try {
        $resp = $client->get('https://strowallet.com/api/bitvcard/fetch-nfccard-detail', [
            'headers'         => ['Accept' => 'application/json'],
            'query'           => array_merge(
                ['public_key' => $publicKey, 'card_id' => $cardId],
                stroModeParam()
            ),
            'connect_timeout' => 15,
            'timeout'         => 45,
            'http_errors'     => false,
        ]);
        $detail = json_decode((string) $resp->getBody(), true);
    } catch (\Throwable $e) {
        \Log::channel('strowallet')->warning('reveal: detail fetch failed', ['card_id' => $cardId, 'e' => $e->getMessage()]);
        return ['success' => false, 'message' => 'Could not reach the card partner.'];
    }

    $cardDetail = $detail['response']['card_detail'] ?? null;
    if (!is_array($cardDetail)) {
        return ['success' => false, 'message' => 'Card details are not available yet.'];
    }

    return [
        'success'         => true,
        'card_number_url' => $cardDetail['card_number_url'] ?? null,
        'cvv_url'         => $cardDetail['cvv_url'] ?? null,
        'last4'           => $cardDetail['last4']  ?? null,
        'expiry'          => $cardDetail['expiry'] ?? null,
    ];
}