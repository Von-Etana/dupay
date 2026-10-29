<?php

use App\Models\PaymentGateway;
use App\Models\Telecom\VirtualAccountType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; 


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException; 
use GuzzleHttp\Psr7\Request;



/************************************************
            by Ibrahim Yekeen Olamikan
            iSmart Olami Technology
**************************************************/

class iSmartFlutterWave
{
    public function __construct()

    {  try {
        $this->gateWay = PaymentGateway::where("name", "Flutterwave")->first();
        $this->apiPayLoad = json_decode($this->gateWay->value_key, true);
        if(!$this->gateWay || $this->apiPayLoad == null ||  !is_array($this->apiPayLoad)){
         return [
             "success" => false,
             "message" => "Payment Value Key not valid contact developer"
         ];
     }
     $this->secret_key = $this->apiPayLoad["secret_key"];
     $this->gatewayId = $this->gateWay->id;
    } catch (\Throwable $th) {
            //throw $th;
        }
    }



     function sendOTP($user, $appName = "PayDemNow", $length = 4, $medidum = "sms"){

        try {

            $endpoint = "https://api.flutterwave.com/v3/otps";
            $header = [
                "authorization: Bearer ".$this->secret_key,
                "Content-Type: application/json",
                "cache-control: no-cache"
            ];
            $payload = [
                "length" => $length,
                "customer" => [
                "name" => $user->profile->username,
                "email" => $user->email,
                 "phone" => $user->phone_number
                ],
             "sender"=> $appName,
             "send"=> true,
            "medium" => [
                $medidum
                ],
             "expiry" => 25
            ];
            $json = postHttp($payload, $endpoint, $header);

            if ($json != null && $json->status == "success") {
                return [
                    "success" => true,
                    "message" => "OTP generated successfully",
                    "data" => $json->data
                ];
            }

            return [
                "success" => false,
                "message" => $json->message ?? "No Response",
            ];
        } catch (\Throwable $th) {
            //throw $th;
        }

    }


 function verifyTransaction($id, $amount, $currency = "NGN"){

        if($this->secret_key == null){
            return [
                "success" => false,
                "message" => "Payment Gateway not available contact admin for support",
            ];
        }

        $endpoint = "https://api.flutterwave.com/v3/transactions/$id/verify";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $json = getHttp($endpoint, $header);

        if ($json != null && $json->status == "success" && $json->data->currency == $currency && $json->data->amount == $amount ) {
            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data
            ];
        }

      return [
            "success" => false,
            "message" => $json->message,
            "data" => $json->data
        ];;
    }


    //create new virtual account number for user then store in database if the account meant to be parmernent
 function generatVirtualAccount($user, $bvn = null, $permanent = false, $amount = 0)
{
    //try {
        $user = $user ?? Auth::user();

        $bvn = $bvn ?? $user->walletVerification->bvn;

        if($bvn != null){
            $permanent == true;
        }
        $accountName = $user->profile->username." ".getSiteSettings("title");

        $endpoint = "https://api.flutterwave.com/v3/virtual-account-numbers";
                $payload = [
                    "tx_ref" =>  strtoupper(Str::random(8)),
                    "narration" => $accountName,
                    "email" => str_replace(' ', '', $user->email),
                    "is_permanent" => $permanent,
                    "amount" => $amount,
                    "firstname" => $user->profile->first_name,
                    "lastname" => $user->profile->last_name,
                    "bvn" => $bvn
                ];
                $header = [
                    "authorization: Bearer ".$this->secret_key,
                    "Content-Type: application/json",
                    "cache-control: no-cache"
                ];
                $json = postHttp($payload, $endpoint, $header);

                if ($json != null && $json->status == "success") {
                    $accountNumber = $json->data->account_number;
                    $banName = $json->data->bank_name;
                    $orderRef = $json->data->order_ref;

                        if (!empty( $accountNumber) &&  !empty($banName)) {
                            if($permanent){
                            $vAccountType = VirtualAccountType::where("name",$banName )->first();
                            $user->virtualAccount()->firstOrCreate(
                                [
                                    //"gateway_id" => $this->gatewayId,
                                    "virtual_account_type_id" => $vAccountType->id ?? "",
                                    "account_name" => $accountName,
                                    "account_number" => $accountNumber,
                                    "bank_name" => $banName ,
                                    "account_reference" => $orderRef,
                                ]
                            );
                        }

                        return [
                            "success" => true,
                            "account_number" => $accountNumber,
                            "bank_name" => $banName,
                            "message" => $json->message,
                            "note" => $json->data->note

                        ];
                        }

                }

                return [
                    "success" => false,
                    "message" => $json->message ?? "No Response",
                ];


   // } catch (\Throwable $th) {
        //throw $th;
    //}

}

//delete virtual account from flutter wave usig Id
 function deleteVirtualAccount($orderRef){

    try {

        $endpoint = "https://api.flutterwave.com/v3/virtual-account-numbers/$orderRef";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $payload = [
            "status" =>  "inactive"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {
            return [
                "success" => true,
                "message" => "Virtual Account Deleted Successfully",
            ];
        }

        return [
            "success" => false,
            "message" => $json->message ?? "No Response",
        ];
    } catch (\Throwable $th) {
        //throw $th;
    }

}

    //create virtual account
     function createVirtualCard($user, $amount, $currency = "NGN", $dbCurrency = "NGN"){
       try {

        $user = $user ?? Auth::user();


        $endpoint = "https://api.flutterwave.com/v3/virtual-cards";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];

        $payload = [
            "currency" =>  $currency,
            "debit_currency" => $dbCurrency,
            "amount" => $amount,
            "billing_name" => $user->walletVerification->name,
            "billing_address" => "abuja",
            "billing_city" => "abuja",
            "billing_state" => "abuja",
            "billing_postal_code" => "900001",
            "billing_country" => "NG",
            "email" => $user->email,
            "phone" => $user->phone_number,
            "first_name" => $user->profile->first_name,
            "last_name" => $user->profile->last_name,
            "date_of_birth" => "1992/01/08",
            "title" => "MR",
            "gender" => "M",
            "callback_url" =>  "https://paydemnow.com/vcard/$user->email"

        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {

        $vCard =    $user->virtualCard()->firstOrCreate(
                [
                    "account_id" =>$json->data->account_id,
                    "card_id" =>$json->data->id,
                    "amount" => $json->data->amount,
                    "currency" => $json->data->currency,
                    "card_hash" => $json->data->card_hash ?? "",
                    "card_pan" => $json->data->card_pan ?? "",
                    "masked_pan" => $json->data->masked_pan ?? "",
                    "city" => $json->data->city ?? "",
                    "state" => $json->data->state ?? "",
                    "address_1" => $json->data->address_1 ?? "",
                    "address_2" => $json->data->address_2 ?? "",
                    "cvv" => $json->data->cvv ?? "",
                    "expiration" => $json->data->expiration ?? "",
                    "send_to" => $json->data->send_to ?? "",
                    "card_type" => $json->data->card_type ?? "",
                    "bin_check_name" => $json->data->bin_check_name ?? "",
                    "name_on_card" => $json->data->name_on_card ?? "",
                    "is_active" => $json->data->is_active ?? false,
                    "callback_url" => $json->data->callback_url ?? ""
                ]
            );

            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data
            ];

        }
        return [
            "success" => false,
            "message" => $json->message ?? "No Response",

        ];

       } catch (\Throwable $th) {
        throw $th;
       }

    }

 function getAllVirtualCards(){

    try {

    $endpoint = "https://api.flutterwave.com/v3/virtual-cards";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];


    } catch (\Throwable $th) {
        //throw $th;
    }

}

//get details of a virtual card
 function getVirtualCardDetail($id){

    try {


    $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];


    } catch (\Throwable $th) {
        //throw $th;
    }

}


 function fundVirtualCard($id, $amount, $dbCurrency = "NGN"){

    try {

        $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id/fund";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $payload = [
            "debit_currency" => $dbCurrency,
            "amount" => $amount
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {
            return [
                "success" => true,
                "message" => $json->message,
            ];
        }

        return [
            "success" => false,
            "message" => $json->message ?? "No Response",
        ];
    } catch (\Throwable $th) {
        //throw $th;
    }

}


 function withFundFromVirtualCard($id, $amount){

    try {

        $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id/withdraw";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $payload = [
            "amount" => $amount
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {
            return [
                "success" => true,
                "message" => $json->message,
            ];
        }

        return [
            "success" => false,
            "message" => $json->message ?? "No Response",
        ];
    } catch (\Throwable $th) {
        //throw $th;
    }

}


 function updateVirtualCardStatus($id, $status){
    try {

        $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id/status/$status";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        $header
    );
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
        ];
    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];

    } catch (\Throwable $th) {
        //throw $th;
    }
}

 function terminateVirtualCard($id){
    try {

        $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id/terminate";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        $header
    );
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
        ];
    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];

    } catch (\Throwable $th) {
        //throw $th;
    }
}


 function getVirtualCardTransaction($id, $size=20, $from =null, $to=null, $index=null){

    try {


    $endpoint = "https://api.flutterwave.com/v3/virtual-cards/$id/transactions?from&to&size=$size&index=0";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];


    } catch (\Throwable $th) {
        //throw $th;
    }

}


function getBankList($country = "NG"){
    $endpoint = "https://api.flutterwave.com/v3/banks/$country";
    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];

}



/*****
 *
 *
 * Verify Bank Details with below possible response
 * "data": {
 *    "account_number": "0177733971",
 *    "account_name": "Ibrahim  Yekeen Olamilekan"
 *  }
 *
 *
 *
 */





 function verifyBankDetails($accountNumber, $bankCode){
try {

        $endpoint = "https://api.flutterwave.com/v3/accounts/resolve";
        $header = [
            "authorization: Bearer ".$this->secret_key,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $payload = [
            "account_bank" =>  $bankCode,
            "account_number" => $accountNumber
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {
            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data,
            ];
        }

        return [
            "success" => false,
            "message" => $json->message ?? "No Response",
        ];
} catch (\Throwable $th) {
    //throw $th;
}
}



 function initiateTransferToBank($bankCode, $accountNumber, $amount, $desc, $trx){

//     try {
        
//             //  $client = new Client();
//             //     $response = $client->post('https://payscribe.ng/api/v1/payouts/transfer', [
//             //         'headers' => [
//             //               'Content-Type: application/json',
//             //     'Authorization: Bearer ps_sk_live_0(SFA,L-2tXVgWRiN6ym4Y3GR)'
//             //         ],
                    
//             //         'json' => [ 
//             //         "country_code" => "ngn",
//             //         "note"=> "Testing Payscribe transfer service...",
                    
//             //             "bank_code" =>  $bankCode,
//             //             "account_number" => $accountNumber,
//             //             "account_name" => "test test",
//             //             "amount" => $amount,
//             //             "currency_id" => "NGN",
//             //             "transaction_reference" => $trx,
//             //             "remark" => 'sdsdsd',
                        
//             //         ],
//             //     ]);
            
//             $client = new Client();
//             $headers = [ 
//                 'Authorization' => 'Bearer ps_sk_live_0(SFA,L-2tXVgWRiN6ym4Y3GR',
//             ];
            
//             $body = [
//                 "reference" => "your-unique-transaction-reference-001878979",
//                 "destination" => [
//                         "country_code" => "ngn",
//                         "note"=> "Testing Payscribe transfer service...",
//                         "bank_code" =>  $bankCode,
//                         "account_number" => $accountNumber,
//                         "account_name" => "test test",
//                         "amount" => $amount,
//                 ]
//             ];
            
            
            
//             // Send the request
//             $response = $client->post('https://payscribe.ng/api/v1/payouts/transfer', [
//                 'headers' => $headers,
//                 'json' => $body
//             ]);
            
   
//                 $json  = json_decode($request->getBody(), true);
//                     Log::error('This is fsdsd'. $json);
                
//                 //   if ($json != null && $json->status == "success") {
//                 //         return [
//                 //             "success" => true,
//                 //             "message" => $json->message,
//                 //             "data" => $json->data,
//                 //         ];
//                 //     }
            
//                 //     return [
//                 //         "success" => false,
//                 //         "message" => $json->message." message from flutterwave" ?? "No Response",
//                 //     ];
        
        
                
//     }catch (ClientException $e) {
//     $response = $e->getResponse();
//     $statusCode = $response ? $response->getStatusCode() : 'No response';
//     $body = $response ? $response->getBody()->getContents() : 'No response body';
//     Log::error('ClientException - Status Code: ' . $statusCode . ' - Body: ' . $body);
// } catch (RequestException $e) { 
//                 $response = $e->getResponse();
//                 $body = $response ? $response->getBody()->getContents() : null;
//                  Log::error('This is life'. $e);
                
//                 //return response()->json(['message' => $body ?? 'Request failed', 'code' => 200], 200);
//             } catch (\Exception $e) {
//                 //return response()->json(['message' => 'An unexpected error occurred.'.$e, 'code' => 200], 200);
//                  Log::error('mad oo'. $e);
//             }
                
                
                try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ps_pk_live_edT5aesDw69TU8eu6EJmhlI6wFfd3A',
        'Content-Type' => 'application/json',
    ])->post('https://api.payscribe.ng/api/v1/payouts/transfer', [
        
        
        
    "currency" => "ngn",
    
    
    
        "amount" =>  $amount,
        "bank" => $bankCode, 
        "account" =>  $accountNumber,
        "country_code" => "ngn",
        "narration" => 'jfggfjhg hgjh',
        "note" => "Testing Payscribe transfer service...",
        "ref"=> 'fhgghfhd gfhgfh  fhg'
    ]);

    if ($response->successful()) {
        // Handle successful response
        $responseData = $response->json();
        Log::info('Transfer successful', $responseData);
        return $responseData;
    } else {
        // Log error response for debugging
        Log::error('Transfer failed', [
            'status' => $response->status(),
            'body' => $response,
        ]);
        return response()->json(['message' => 'Transfer failed', 'error' => $response->json()], $response->status());
    }
} catch (\Exception $e) {
    Log::error('Exception during transfer', ['error' => $e->getMessage()]);
    return response()->json(['message' => 'An unexpected error occurred.', 'error' => $e->getMessage()], 500);
}
                
                
                
                
 
  

}



 function initiateTransferToBankold($bankCode, $accountNumber, $amount, $desc, $trx){

    try {

        $endpoint = "https://api.flutterwave.com/v3/transfers";
        $header = [
            "authorization: Bearer vvbb",
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        $payload = [
            "account_bank" =>  $bankCode,
            "account_number" => $accountNumber,
            "amount" => $amount,
            "narration" => $desc,
            "currency" => "NGN",
            "reference" => $trx,
            "debit_currency" => "NGN"
        ];
        $json = postHttp($payload, $endpoint, $header);

        if ($json != null && $json->status == "success") {
            return [
                "success" => true,
                "message" => $json->message,
                "data" => $json->data,
            ];
        }

        return [
            "success" => false,
            "message" => $json->message." message from flutterwave" ?? "No Response",
        ];
    } catch (\Throwable $th) {
        dd($th); 
        Log::error('Error'. $th);
    }

}



 function retryTransferToBank($id){

    try {


    $endpoint = "https://api.flutterwave.com/v3/transfers/$id/retries";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];

    } catch (\Throwable $th) {
        //throw $th;
    }

}


 function getAllTransfer($page, $status ){

    try {


    $endpoint = "https://api.flutterwave.com/v3/transfers?page=$page&status=$status";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];


    } catch (\Throwable $th) {
        //throw $th;
    }

}

 function getTransferDetail($id){

    try {


    $endpoint = "https://api.flutterwave.com/v3/transfers/$id";

    $header = [
        "authorization: Bearer ".$this->secret_key,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ];
    $json = getHttp($endpoint, $header);

    if ($json != null && $json->status == "success") {
        return [
            "success" => true,
            "message" => $json->message,
            "data" => $json->data
        ];

    }

    return [
        "success" => false,
        "message" => $json->message ?? "No Response",
    ];


    } catch (\Throwable $th) {
        //throw $th;
    }

}


}
