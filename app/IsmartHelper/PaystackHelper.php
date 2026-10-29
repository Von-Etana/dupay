<?php

use App\Models\PaymentGateway;

/************************************************
            by Ibrahim Yekeen Olamikan
            iSmart Olami Technology
 **************************************************/

class iSmartPaystack
{

    public function __construct()
    {
        $this->gateWay = PaymentGateway::where("name", "like", "%Paystack%")->firstOrFail();
        $this->apiPayLoad = json_decode($this->gateWay->value_key, true);
        $this->secret_key = $this->apiPayLoad["secret_key"];
        $this->public_key = $this->apiPayLoad["public_key"];
    }



    function getBankList($currency = "NGN")
    {

        $endpoint = "https://api.paystack.co/bank?currency=$currency";
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $json = getHttp($endpoint, $header);
        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }


    function  verifyBankDetails($name, $accountNumber, $bankCode)
    {
        $endpoint = "https://api.paystack.co/transferrecipient";
        $payload = [
            "type" => "nuban",
            "name" => $name,
            "account_number" => $accountNumber,
            "bank_code" => $bankCode,
            "currencyCode" => "NGN",
        ];
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "recipient_code" => $json->data->recipient_code,
                "data" => $json->data->details,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }



    function  transferToBank($amount, $reference, $recipient, $reason)
    {
        $endpoint = "https://api.paystack.co/transfer";
        $payload = [
            "source" => "balance",
            "amount" => $amount * 100,
            "reference" => $reference,
            "recipient" => $recipient,
            "reason" => $reason
        ];
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }



    function fetchTransafer($id)
    {
        $endpoint = "https://api.paystack.co/transfer/$id";
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $json = getHttp($endpoint, $header);
        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "message" => $json->message,
                "status" => $json->data->status,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }


    function verifyTransafer($reference)
    {
        $endpoint = "https://api.paystack.co/transfer/verify/$reference";
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $json = getHttp($endpoint, $header);
        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "message" => $json->message,
                "status" => $json->data->status,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }




    function  createCustomer($user)
    {
        $endpoint = "https://api.paystack.co/customer";
        $payload = [
            "email" => $user->email,
            "first_name" => $user->profile->first_name,
            "last_name" => $user->profile->last_name,
            "phone" => $user->phone_number,
        ];
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {
            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }


    //INSERT INTO `basic_settings` (`id`, `name`, `description`, `input_type`, `column_name`, `group_type`, `required`, `rule`, `model`, `value`, `input_option`, `api_visibility`, `created_at`, `updated_at`) VALUES (NULL, 'Default Virtual Account Gateway', 'Default Virtual Account Gateway', '6', 'default_virtual_account_gateway', '', 'required', '', 'system', 'Paystack', '[\"Paystack\", \"Flutterwave\", \"Monnify\"]', '1', '2023-05-24 10:20:44', '2023-06-04 15:34:21');

    function  createDedicatedVirtualAccount($user)
    {

        $customer = $this->createCustomer($user);

        if (!$customer["success"]) {
            return [
                "success" => false,
                "message" => $customer["message"]
            ];
        }


        $endpoint = "https://api.paystack.co/dedicated_account";
        $payload = [
            "customer" => $customer["data"]->id,
            "preferred_bank" => "wema-bank"
        ];
        $header = [
            "authorization: Bearer " . $this->secret_key,
            "Content-Type: application/json"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json == null) {
            return [
                "success" => false,
                "message" => "Unable to complete request"
            ];
        }

        if ($json->status == true) {

            //store virtual account
            $user->virtualAccount()->firstOrCreate(
                [
                    //"virtual_account_type_id" => $vAccountType->id ?? "",
                    "account_name" => $json->data->account_name,
                    "account_number" => $json->data->account_number,
                    "bank_name" => $json->data->bank->name,
                    "account_reference" => $json->data->id,
                    "gateway" => "Paystack"
                ]
            );

            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data,
            ];
        } else {
            return [
                "success" => false,
                "message" => $json->message
            ];
        }
    }
}
