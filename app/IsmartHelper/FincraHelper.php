<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;


function createFincraVirtualAccount(array $data)
{
    try {
        $client = new Client([
            'base_uri' => 'https://sandboxapi.fincra.com',
            'timeout'  => 30,
        ]);

        $payload = [
            "currency"      => $data['currency'],
            "accountType"   => $data['accountType'] ?? 'individual',
            "utilityBill"   => $data['utilityBill'],
            "bankStatement" => $data['bankStatement'],
            "meansOfId"     => $data['meansOfId'],
            "KYCInformation" => [
                "address" => [
                    "state"              => $data['KYCInformation']['address']['state'],
                    "city"               => $data['KYCInformation']['address']['city'],
                    "street"             => $data['KYCInformation']['address']['street'],
                    "zip"                => $data['KYCInformation']['address']['zip'],
                    "countryOfResidence" => $data['KYCInformation']['address']['countryOfResidence'],
                    "number"             => $data['KYCInformation']['address']['number'],
                ],
                "email"                  => $data['KYCInformation']['email'],
                "incomeBand" => [
                    "lower" => $data['KYCInformation']['incomeBand']['lower'],
                    "upper" => $data['KYCInformation']['incomeBand']['upper'],
                ],
                "sourceOfIncome"        => $data['KYCInformation']['sourceOfIncome'],
                "accountDesignation"    => $data['KYCInformation']['accountDesignation'],
                "phone"                 => $data['KYCInformation']['phone'],
                "occupation"            => $data['KYCInformation']['occupation'],
                "nationality"           => $data['KYCInformation']['nationality'],
                "birthDate"             => $data['KYCInformation']['birthDate'],
                "taxCountry"            => $data['KYCInformation']['taxCountry'] ?? null,
                "taxNumber"             => $data['KYCInformation']['taxNumber'] ?? null,
                "firstName"             => $data['KYCInformation']['firstName'],
                "lastName"              => $data['KYCInformation']['lastName'],
                "otherName"             => $data['KYCInformation']['otherName'] ?? null,
                "document" => [
                    "type"              => $data['KYCInformation']['document']['type'],
                    "number"            => $data['KYCInformation']['document']['number'],
                    "issuedCountryCode" => $data['KYCInformation']['document']['issuedCountryCode'],
                    "issuedBy"          => $data['KYCInformation']['document']['issuedBy'],
                    "issuedDate"        => $data['KYCInformation']['document']['issuedDate'],
                    "expirationDate"    => $data['KYCInformation']['document']['expirationDate'],
                ],
                "employmentStatus"      => $data['KYCInformation']['employmentStatus'],
                "monthlyTransactionCount"  => $data['KYCInformation']['monthlyTransactionCount'],
                "monthlyTransactionVolume" => $data['KYCInformation']['monthlyTransactionVolume'],
            ]
        ];

        $response = $client->request('POST', '/profile/virtual-accounts/requests', [
            'headers' => [
                'accept'        => 'application/json',
                'content-type'  => 'application/json',
                'api-key'       => env('FINCRA_API_KEY'),
            ],
            'json' => $payload
        ]);

        $result = json_decode($response->getBody(), true);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
            'data'    => $result['data'] ?? [],
        ];

    } catch (\Exception $e) {
        Log::warning('Fincra Webhook: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'data'    => []
        ];
    }
}

function validateFincraWebhook(Request $request): bool
{
    $secret = config('services.fincra.webhook_secret');
    $signature = $request->header('x-fincra-signature');

    if (!$signature || !$secret) {
        Log::warning('Fincra Webhook: Missing signature or configured secret');
        return false;
    }

    $payload   = $request->getContent();
    $computed  = hash_hmac('sha512', $payload, $secret);

    return hash_equals($signature, $computed);
}
