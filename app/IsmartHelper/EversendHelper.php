<?php

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

function eversendAccessToken()
{
    return Cache::remember('eversend_auth_token', now()->addMinutes(30), function () {
        try {
            $client = new Client();

            $response = $client->request('GET', 'https://api.eversend.co/v1/auth/token', [
                'headers' => [
                    'accept' => 'application/json',
                    'clientId' => env('EVERSEND_CLIENT_ID'),
                    'clientSecret' => env('EVERSEND_CLIENT_SECRET'),
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            if (($body['status'] ?? 0) === 200 && isset($body['token'])) {
                return $body['token'];
            }

            Log::warning('Eversend: Invalid response received', $body);
            return null;

        } catch (\Exception $e) {
            Log::error('Eversend Auth Token Error: ' . $e->getMessage());
            return null;
        }
    });
}

function updateEversendCardUser(array $payload)
{
    try {
        $client = new Client();

        $token = eversendAccessToken();

        if (!$token) {
            throw new \Exception('Unable to retrieve Eversend auth token.');
        }

        $response = $client->request('PATCH', 'https://api.eversend.co/v1/cards/user', [
            'headers' => [
                'Authorization' => "Bearer $token",
                'accept'        => 'application/json',
                'content-type'  => 'application/json',
            ],
            'json' => [
                'phone'     => $payload['phone'],
                'country'   => $payload['country'],  
                'state'     => $payload['state'],
                'city'      => $payload['city'],
                'address'   => $payload['address'],
                'zipCode'   => $payload['zipCode'],
                'idType'    => $payload['idType'], 
                'idNumber'  => $payload['idNumber'],
                'id'        => $payload['id'],
            ],
        ]);

        return json_decode($response->getBody(), true);

    } catch (\Exception $e) {
        Log::error('Eversend Update Card User Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update card user',
            'error' => $e->getMessage()
        ];
    }
}

function getEversendCardUsers(int $page = 1, int $limit = 20)
{
    try {
        $client = new Client();

        $token = eversendAccessToken();

        if (!$token) {
            throw new \Exception('Unable to retrieve Eversend auth token.');
        }

        $response = $client->request('GET', "https://api.eversend.co/v1/cards/getUsers?page=$page&limit=$limit", [
            'headers' => [
                'accept'        => 'application/json',
                'authorization' => "Bearer $token",
            ],
        ]);

        $body = json_decode($response->getBody(), true);

        return $body['data']['data']['users'] ?? [];

    } catch (\Exception $e) {
        Log::error('Failed to fetch Eversend card users: ' . $e->getMessage());
        return [];
    }
}

