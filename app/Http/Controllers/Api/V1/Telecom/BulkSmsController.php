<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\BulkSms;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;

class BulkSmsController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    public function sendBulkSMS(Request $request){
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'receiver' => 'required',
            'sender' => 'required',
            'message' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();


        //check transaction pin
        if ($request->header('HTTP_ISMART') == null || $request->header('HTTP_ISMART') != 'api') {
            if ($request->transaction_pin == null || $request->transaction_pin == "") {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            }
            elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            }
            elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }




        $Trx = strtoupper(Str::random(16));
        $item = BulkSms::where("status", "1")->first();

        if (!$item) {
            return response(['success' => false, 'message' => "Bulk SMS Service not available try again later"]);
        }

        if ($item->apiConnection == null || $item->apiConnection->status == "0") {
            return response(['success' => false, 'message' => "Bulk SMS Connection is down contact admin"]);
        }

        $sender = $request->sender;
        $receiver = $request->receiver;
        $message = $request->message;
        $recivers = explode(",", $receiver);

        // sum of number & message
		$receiver_length = count($recivers);
	    $message_length = strlen($message)/160;
		$word_length = strlen($message);

            // Determining price
			if ($message_length <= 1) {
				$length = 1;
			} else if ($message_length > 1 || $message_length == 2) {
				$length = 2;
			} else if ($message_length > 2 || $message_length == 3) {
				$length = 3;
			} else if ($message_length > 3 || $message_length == 4) {
				$length = 4;
			} else if ($message_length > 4 || $message_length == 5) {
				$length = 5;
			}

        //     $userTransaction = $user->transaction()->latest()->first();
        // if($userTransaction){
        //     if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "SMS")
        //     {
        //         //$tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
        //         return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

        //     }
        // }

        //check selected wallet
        $select_wallet_id = $request->wallet_id ?? "1";
        if (!checkWalletEnable("BulkSms", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }

            // Amount
			 $amount = $length * $receiver_length * $item->sms_charge;
             $checkWallet =  checkUserWallet($user, $amount, $select_wallet_id);
             if (!$checkWallet) {
                 return response(['success' => false, 'message' => "Insufficient Funds"]);
             }

            //  $wallet = $user->balance;
            //  $balance = $wallet - $amount;

             $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
             $init_balance = $balanceAction["init_balance"];
             $final_balance = $balanceAction["final_balance"];


             if ($balanceAction["success"]) {
                $description = "$length page(s) of sms | message length = $word_length";
                $Trx = strtoupper(Str::random(16));
                $ul['wallet_id'] = $select_wallet_id;
                $ul['user_level_id'] = $user->user_level_id;
                $ul['service'] = "SMS";
                $ul['service_type'] = "Bulk SMS";
                $ul['description'] = $description;
                $ul['amount'] = $amount;
                $ul["message"] = null;
                $ul['phone_number'] = $receiver;
                $ul['type'] = "0";
                $ul['initial_balance'] = $init_balance;
                $ul['final_balance'] = $final_balance;
                $ul['reference'] = $Trx;
                $ul['status'] = "pending";
                $tansaction =  $user->transaction()->create($ul);
            }else{
                return response(['success' => false, 'message' => $balanceAction["message"] ?? "Wallet balance error"]);
            }

            $sucess = false;
            $apiSuccessField = $item->apiConnection->success_field;
            $apiSuccessResponse = $item->apiConnection->success_response;
            $endpoint = $item->apiConnection->url;

              //header
              $Myheader = getHeaderApiConnection($item->apiConnection);
              $header = $Myheader["header"];
              $token = $Myheader["token"];

              if($item->apiConnection->method == "1"){

                //payload
                $apiPayLoad = json_decode($item->apiConnection->payload, true);
                $payload = array();
                if($apiPayLoad == null ||  !is_array($apiPayLoad)){
                    return response(['success' => false, 'message' => "Service Connection playload not active contact admin"]);
                }else{
                    foreach ($apiPayLoad as $key => $value) {
                        $value = str_replace("{{sender}}",$sender,$value);
                        $value = str_replace("{{phoneNumber}}",$receiver,$value);
                        $value = str_replace("{{message}}",$message,$value);
                        $value = str_replace("{{token}}",$token,$value);
                        $value = str_replace("{{referenceNumber}}",$Trx,$value);
                        $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                        $payload[$key] = $value;
                    }
                }
                $json = postHttp($payload, $endpoint, $header);
              }else{
                $endpoint = str_replace("{{sender}}",$sender,$endpoint);
                $endpoint = str_replace("{{phoneNumber}}",$receiver,$endpoint);
                $endpoint = str_replace("{{message}}",$message,$endpoint);
                $endpoint = str_replace("{{token}}",$token,$endpoint);
                $endpoint = str_replace("{{referenceNumber}}",$Trx,$endpoint);
                $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
                $json = getHttp($endpoint, $header);
            }


            if ($json != null) {
                $reponseVal = $json;
                foreach(explode('->', $apiSuccessField) as $item) {
                $reponseVal = $reponseVal->$item;
                }
                if ($reponseVal == $apiSuccessResponse) {
                    $sucess = true;
                } else {
                    $sucess = false;
                }
            }



        //check if success and Update Record
        if ($sucess) {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "success"]);
            if ($query) {
                $description = "$length page(s) of sms | message length = $word_length Reference ID Is : #$Trx";
                if (getSiteSettings("email_notify") == "Yes") {
                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Bill Payment', $description);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes" && $user->phone_number != null) {
                    try {
                        // $this->sendSms($user->phone_number, $description);
                        sendTransactionSms($user, $description, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                (new FirebaseUserUpdate)->addUserNotification($user,
                [
                    "message" => $description,
                    "type" => "transaction",
                    "read" => 0,
                    "data" => [
                        "service" =>'BULK SMS',
                        "id" => $tansaction->id,
                        "reference_id" => $Trx,
                        "status" => "success",
                        'date' => $date
                    ]
                ]
                );

                (new FirebaseUserUpdate)->updateUser($user);

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'successful',
                    'data' => [
                        'service' => "Bulk SMS",
                        'amount' => $amount,
                        'date' => $date,
                        'reference' => $Trx,
                        'wallet_balance' => $final_balance,
                    ],
                ]);
            }
        } else {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful"]);
            if ($query) {
                //refund user

                $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
                $init_balance = $balanceAction["init_balance"];
                $final_balance = $balanceAction["final_balance"];
                (new FirebaseUserUpdate)->updateUser($user);

                if ($balanceAction["success"]) {
                    $description = "$length page(s) of sms | message length = $word_length Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['service'] = "SMS";
                    $ul['service_type'] = "Bulk SMS";
                    $ul['description'] = $description;
                    $ul['amount'] = $amount;
                    $ul['phone_number'] = $receiver;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $init_balance;
                    $ul['final_balance'] = $final_balance;
                    $ul['reference'] = $trx;
                    $ul['status'] = "refund";
                    $tansaction =  $user->transaction()->create($ul);
                }
            }
            return response()->json([
                'success' => false,
                'status' => 'unsuccessful',
                'message' => 'An error occured please ensure company name such as MTN, DSTV etc are not included in your message',
                'data' => [
                    'reference' => $Trx,
                    'date' => $date
                ],
            ]);
        }

    }


}
