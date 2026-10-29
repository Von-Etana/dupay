<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Transaction;

function sendTransactionSms($user, $message, $select_wallet_id = null)
{
    $smsCharge = (float) getSiteSettings('sms_charges');

    if ($user->profile->receive_sms != 1 || $user->balance < $smsCharge) {
                Log::info('SMS Sent:', ['response' => 'bad']);

        return false;
    }

    try {
        $client = new Client();
        $response = $client->request('POST', 'https://strowallet.com/api/bulk-sms/', [
            'query' => [
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                'sender_id' => 'Dupay',
                'message' => $message,
                'phone' => $user->phone_number,
            ],
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        Log::info('SMS Sent:', ['response' => $body]);

        $check_balance = checkUserWallet($user, $smsCharge, "1");
        if (!$check_balance) {
            return false;
        }

        // Explicitly exempt from the daily limit: this is a per-alert fee charged
        // AFTER the transaction the user actually initiated has already been
        // checked and debited. Letting it be refused would leave a completed
        // purchase with a failed alert charge, and would double-count against
        // the user's limit.
        $balanceAction = userBalanceAction($user, $smsCharge, '1', "-", false);
        if (!$balanceAction || !isset($balanceAction["init_balance"], $balanceAction["final_balance"])) {
            return false;
        }

        $init_balance  = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];
        $realPrice     = $smsCharge;

        $description = "SMS Notification charge for transaction alert to {$user->phone_number}";
        $Trx = strtoupper(Str::random(16));

        $ul = [
            'wallet_id'         => $select_wallet_id,
            'user_level_id'     => $user->user_level_id,
            'service'           => 'SMS Notification',
            'api_connection_id' => null,
            'service_type_id'   => null,
            'service_plan_id'   => null,
            'service_type'      => 'Notification',
            'description'       => $description,
            'amount'            => $smsCharge,
            'real_price'        => $realPrice,
            'charge'            => 0,
            'phone_number'      => $user->phone_number,
            'type'              => '0',
            'initial_balance'   => $init_balance,
            'final_balance'     => $final_balance,
            'reference'         => $Trx,
            'status'            => 'success',
        ];

        $transaction = $user->transaction()->create($ul);

        return true;
    } catch (RequestException $e) {
        // Log HTTP request error
        if ($e->hasResponse()) {
            $errorBody = (string) $e->getResponse()->getBody();
            $decodedError = json_decode($errorBody, true);
            $errorMessage = $decodedError['message'] ?? 'Unknown error from SMS provider';

            Log::error('SMS API Error', [
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $errorMessage,
                'raw' => $errorBody
            ]);
        } else {
            Log::error('SMS Request failed with no response', ['error' => $e->getMessage()]);
        }

        return false;
    } catch (\Exception $e) {
        // Log general error
        Log::error('SMS sending failed', ['error' => $e->getMessage()]);
        return false;
    }
}

