<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


function sudoBankTransfer(array $data): array
{
    $client = new Client();

    $url = "https://api.sudo.africa/accounts/transfer";

    $headers = [
        'Authorization' => env('SUDO_API_KEY'),
        'Content-Type' => 'application/json',
    ];

    $body = [
        'debitAccountId' => $data['debitAccountId'],
        'beneficiaryBankCode' => $data['beneficiaryBankCode'],
        'beneficiaryAccountNumber' => $data['beneficiaryAccountNumber'],
        'amount' => $data['amount'],
        'narration' => $data['narration'],
        'paymentReference' => $data['paymentReference'] ?? uniqid('sudo_'),
    ];

    try {
        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $body
        ]);

        $json = json_decode($response->getBody());

        return [
            'success' => true,
            'message' => $json->message ?? 'Transfer successful',
            'data' => $json ?? null,
        ];
    } catch (RequestException $e) {
        $json = null;

        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $json = json_decode($responseBody);
        }

        return [
            'success' => false,
            'message' => ($json->message ?? 'No Response') . ' message from flutterwave',
        ];
    }
}

function fetchAllBanks()
{
    $client = new Client();
    $apiKey = env('SUDO_API_KEY');

    try {
        $response = $client->request('GET', 'https://api.sudo.africa/accounts/banks', [
            'headers' => [
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['statusCode'] === 200 && $body['responseCode'] === '00') {
            $banks = collect($body['data'])->map(function ($bank) {
                return [
                    'id' => $bank['routingKey'],
                    'code' => $bank['bankCode'],
                    'name' => $bank['name'],
                ];
            })->values()->all();

            return [
                'success' => true,
                'data' => $banks,
            ];
        }

        return [
            'success' => false,
            'message' => $body['message'] ?? 'Unexpected response.',
        ];
    } catch (RequestException $e) {
        Log::error('Guzzle Sudo API error: ' . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Failed to fetch banks.',
            'error' => $e->getMessage(),
        ];
    }
}

function verifySudoAccount($accountNumber, $bankCode)
{
    try {
        $endpoint = "https://api.sudo.africa/accounts/transfer/name-enquiry";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUDO_API_KEY'),
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'no-cache',
        ])->post($endpoint, [
            'bankCode' => $bankCode,
            'accountNumber' => $accountNumber,
        ]);

        if ($response->successful()) {
            $json = $response->object();

            // ✅ Check based on responseCode instead of success/status
            if (
                isset($json->data->responseCode) &&
                $json->data->responseCode === "00"
            ) {
                $datas = [
                    'bank_code'      => $json->data->bankCode ?? null,
                    'account_number' => $json->data->accountNumber ?? null,
                    'account_name'   => $json->data->accountName ?? null,
                ];

                return [
                    'success' => true,
                    'message' => $json->data->responseMessage ?? 'Account verified successfully',
                    'data'    => $datas,
                ];
            }

            return [
                'success' => false,
                'message' => $json->data->responseMessage ?? 'Account verification failed',
                'data'    => $json->data ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => 'Unexpected response from Sudo API',
        ];
    } catch (\Throwable $th) {
        return [
            'success' => false,
            'message' => 'Request failed: ' . $th->getMessage(),
        ];
    }
}
