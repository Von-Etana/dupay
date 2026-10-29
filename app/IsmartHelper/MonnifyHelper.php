<?php

use App\Models\PaymentGateway;
use App\Models\Telecom\VirtualAccountType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


/************************************************
            by Ibrahim Yekeen Olamikan
            iSmart Olami Technology
**************************************************/

class iSmartMonnify
{

    public function __construct()
    {
        $this->gateWay = PaymentGateway::where("name", "like", "%Monnify%")->firstOrFail();
        $this->apiPayLoad = json_decode($this->gateWay->value_key, true);
    //     if(!$this->gateWay || $this->apiPayLoad == null ||  !is_array($this->apiPayLoad)){
    //      return [
    //          "success" => false,
    //          "message" => "Payment Value Key not valid contact developer"
    //      ];
    //  }
     $this->key = $this->apiPayLoad["public_key"] . ':' . $this->apiPayLoad["secret_key"];
     $this->key = base64_encode($this->key);
     $this->gatewayId = $this->gateWay->id;
     $this->contract_code = $this->apiPayLoad["contract_code"];
    }


      function  monnifyToken()
{
    $endpoint = "https://api.monnify.com/api/v1/auth/login";
    $payload = [];
    $header = [
        "authorization: Basic ".$this->key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = postHttp($payload, $endpoint, $header);
    return $json->responseBody->accessToken;
}

     function generateVirtualAccount($id)
{


    $AvailableVirtualAccount = VirtualAccountType::whereStatus("1")->get();
    $BankCode = [];
    foreach ($AvailableVirtualAccount as $acc) {
        array_push($BankCode, $acc->code);
    }

    $aCount = 0;
    //check number of account available
    if (count($AvailableVirtualAccount) > 0 && count($BankCode) > 0) {
            try {
                $token = $this->monnifyToken();
                $user = User::findOrFail($id);
                $endpoint = "https://api.monnify.com/api/v2/bank-transfer/reserved-accounts";
                $payload = [
                    "accountReference" =>  strtoupper(Str::random(8)),
                    "accountName" => $user->profile->getName(),
                    "currencyCode" => "NGN",
                    "contractCode" => $this->contract_code,
                    "customerEmail" => str_replace(' ', '', $user->email),
                    "customerName" => $user->profile->getName(),
                    "getAllAvailableBanks" => false,
                    "preferredBanks" => $BankCode,
                    "bvn" => $user->walletVerification != null ? $user->walletVerification->bvn : null,
                ];
                $header = [
                    "authorization: Bearer $token",
                    "Content-Type: application/json",
                    "cache-control: no-cache"
                ];
                $json = postHttp($payload, $endpoint, $header);
                if ($json != null && $json->responseMessage == "success") {
                    $accountName = $json->responseBody->accountName;
                    $accountReference = $json->responseBody->accountReference;

                    foreach ($json->responseBody->accounts as  $account) {
                        if (!empty( $account->bankName) &&  !empty($account->accountNumber)) {
                            $vAccountType = VirtualAccountType::where("code",$account->bankCode)->first();
                            $user->virtualAccount()->firstOrCreate(
                                [
                                    "virtual_account_type_id" => $vAccountType->id ?? "",
                                    "account_name" => $accountName,
                                    "account_number" => $account->accountNumber,
                                    "bank_name" => $account->bankName,
                                    "account_reference" => $accountReference,
                                    
                                ]
                            );
                        }


                    }
                    return [
                        "success" => true,
                        "message" => "Virtual Account Created Successfully"
                    ];
                    //return true;
                } else {
                    return [
                        "success" => false,
                        "message" => $json
                    ];
                    //return false;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

    }

}


function updateVirtualAccount($id)
{
    $user = User::findOrFail($id);
    //check number of account available
    if (count($user->virtualAccount) > 0) {
        foreach ($user->virtualAccount as  $accountinfo) {
            try {
                $token = $this->monnifyToken();
                $endpoint = "https://api.monnify.com/api/v1/bank-transfer/reserved-accounts/$accountinfo->account_reference/kyc-info";
                
                $payload = [
                    $user->walletVerification->method == null || $user->walletVerification->method == "BVN"  ? "bvn" : "nin" => $user->walletVerification->bvn 
                ];
                $header = [
                    "authorization: Bearer $token",
                    "Content-Type: application/json",
                    "cache-control: no-cache"
                ];
                $json = putHttp($payload, $endpoint, $header);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        if ($json != null && $json->responseMessage == "success") {
            return [
                "success" => true,
                "message" => "Virtual Account Updated Successfully"
            ];
            //return true;
        } else {
            return [
                "success" => false,
                "message" => $json
            ];
        }

    }else{
        $this->generateVirtualAccount($id);
    }

}


 function verififyBVN($payload){
    $token = $this->monnifyToken();
    $endpoint = "https://api.monnify.com/api/v1/vas/bvn-details-match";
    $header = [
        "authorization: Bearer $token",
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = postHttp($payload, $endpoint, $header);
    return $json;
}


function delocateAccount($reference)
{
    $token = $this->monnifyToken();
    $endpoint = "https://api.monnify.com/api/v1/bank-transfer/reserved-accounts/reference/$reference";
    $header = [
        "authorization: Bearer $token",
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);
    return $json;
}



function getPaymentDetails($reference, $type = 1)
{

    $token = $this->monnifyToken();
    if ($type == 1) {
        $endpoint = "https://api.monnify.com/api/v1/merchant/transactions/query?paymentReference=$reference";
    } else {
        $endpoint = "https://api.monnify.com/api/v2/merchant/transactions/query?transactionReference=$reference";
    }
    $header = [
        "authorization: Bearer $token",
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);
    return $json;
}



function verifyPaymentDetails($reference)
{
    $token = $this->monnifyToken();
    $paid = "unsuccessful";
    $endpoint = "https://api.monnify.com/api/v1/merchant/transactions/query?";
    $header = [
        "authorization: Bearer $token",
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $payload = [
        "paymentReference" =>  $reference
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint . http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        $header
    );
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);

    try {
        if ($json != null) {
            if ($json->requestSuccessful == true && $json->responseBody->paymentStatus == "PAID") {
                $paid = "successful";
            }
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
    return $paid;
}

}
