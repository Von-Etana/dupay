<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Cable;
use App\Models\Telecom\CableType;
use App\Models\Telecom\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use iSmartBonus;

class CableController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    //get all cable list
    public function getCableList()
    {
        $item = CableType::where("status", "1")
            ->with("cableList")
            ->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No available cable"]);
        }

        return response(['success' => true, "data" => $item]);
    }

    /*******
     *
     *
     *
     *  Cable Verification
     *
     *
     */

    public function verifyCableNumber(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'plan_id' => 'required',
            'cable_id' => 'required',
            'decoder_number' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $plan = Cable::find($request->plan_id);
        $apiTv = $plan->cableType->name;


        if (!$plan) {
            return response(['success' => false, 'message' => "Plan Does not Exist"]);
        }

        if ($plan->cableType->status == "0" || $plan->status == "0") {
            return response(['success' => false, 'message' => "Plan Is Currently not Available try again later"]);
        }

        if (($plan->cableType->apiVerificationConnection == null || $plan->cableType->apiVerificationConnection->status == "0")) {
            return response(['success' => false, 'message' => "Verification Connection is currently down contact admin"]);
        }

        $endpoint =  $plan->cableType->apiVerificationConnection->url;

        //header
        $Myheader = getHeaderApiConnection($plan->cableType->apiVerificationConnection);
        $header = $Myheader["header"];
        $token = $Myheader["token"];

        if ($plan->cableType->apiVerificationConnection->method == "1") {

            //payload
            $apiPayLoad = json_decode($plan->cableType->apiVerificationConnection->payload, true);
            $payload = array();
            if ($apiPayLoad == null ||  !is_array($apiPayLoad)) {
                return response(['success' => false, 'message' => "Service Connection playload not active contact admin"]);
            } else {
                foreach ($apiPayLoad as $key => $value) {
                    $value = str_replace("{{decoderNumber}}", $request->decoder_number, $value);
                    $value = str_replace("{{tvName}}", $apiTv, $value);
                    $value = str_replace("{{tvNameLower}}", strtolower($apiTv), $value);
                    $value = str_replace("{{cableName}}", $apiTv, $value);
                    $value = str_replace("{{cableCode}}", $plan->cableType->code, $value);
                    $value = str_replace("{{phoneNumber}}", $request->phone_number, $value);
                    $value = str_replace("{{planId}}", $plan->id, $value);
                    $value = str_replace("{{cableId}}", $plan->cableType->id, $value);
                    $value = str_replace("{{code}}", $plan->code, $value);
                    $value = str_replace("{{planCode}}", $plan->plan_code, $value);
                    $value = str_replace("{{current_date}}", date("Ymdhis"), $value);
                    $payload[$key] = $value;
                }
            }
            //$json = postHttp($payload, $endpoint, $header);
            if ($plan->cableType->apiVerificationConnection->method == "1") {
                $json = postHttp($payload, $endpoint, $header);
            } else {
                $json = postHttpParameter($endpoint, $payload, $header);
            }
        } else {
            $endpoint = str_replace("{{decoderNumber}}", $request->decoder_number, $endpoint);
            $endpoint = str_replace("{{tvName}}", $apiTv, $endpoint);
            $endpoint = str_replace("{{tvNameLower}}", strtolower($apiTv), $endpoint);
            $endpoint = str_replace("{{cableName}}", $apiTv, $endpoint);
            $endpoint = str_replace("{{cableCode}}", $plan->cableType->code, $endpoint);
            $endpoint = str_replace("{{phoneNumber}}", $request->phone_number, $endpoint);
            $endpoint = str_replace("{{planId}}", $plan->id, $endpoint);
            $endpoint = str_replace("{{code}}", $plan->code, $endpoint);
            $endpoint = str_replace("{{planCode}}", $plan->plan_code, $endpoint);
            $endpoint = str_replace("{{cableId}}", $plan->cableType->id, $endpoint);
            $endpoint = str_replace("{{token}}", $token, $endpoint);
            $endpoint = str_replace("{{current_date}}", date("Ymdhis"), $endpoint);
            $json = getHttp($endpoint, $header);
        }
        //return response(['success' => false, 'message' => $json]);
        $customerName = null;
        $apiPassResponse = false;
        $apiSuccessField = $plan->cableType->apiVerificationConnection->success_field;
        $apiSuccessResponse = $plan->cableType->apiVerificationConnection->success_response;

        try {
            if ($apiSuccessResponse == "not null") {
                if ($json != null) {
                    $areponseVal = $json;
                    foreach (explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                    }
                    if (!empty($areponseVal)) {
                        $customerName = $areponseVal;
                        $apiPassResponse = true;
                    }
                }
            } else {
                if ($json != null) {
                    $areponseVal = $json;
                    foreach (explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                    }
                    if ($areponseVal == $apiSuccessResponse) {
                        $customerName = $areponseVal;
                        $apiPassResponse = true;
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        if (!$apiPassResponse) {
            return response(['success' => false, 'message' => "The decoder number is not associated with any multichoice user. Please recheck the number and retry",]);
        }


        return response(['success' => true, 'message' => $customerName]);
    }




    /*******
     *
     *
     *
     *  Cable Subscription
     *
     *
     */
    public function buyCableSub(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'phone_number' => 'required',
            'plan_id' => 'required',
            'cable_id' => 'required',
            'decoder_number' => 'required',
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
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
        
        if($user->blocked_status == true){
            return response()->json([
                'success' => false,
                'message' => "Your account has been temporarily blocked due to unusual activity. Please contact support to restore access."
            ]);
        }
    
        //check selected wallet
        $select_wallet_id = $request->wallet_id ?? "1";
        if (!checkWalletEnable("Cable", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }
    
        $plan = Cable::find($request->plan_id);
        $apiTv = $plan->cableType->name;
        $TranxMessage = null;
    
        if (!$plan) {
            return response(['success' => false, 'message' => "Plan Does not Exist"]);
        }
    
        if ($plan->cableType->status == "0" || $plan->status == "0") {
            return response(['success' => false, 'message' => "Plan Is Currently not Available try again later"]);
        }
    
        if (($plan->cableType->apiConnection == null || $plan->cableType->apiConnection->status == "0") && $plan->cableType->order_delivery == "1") {
            return response(['success' => false, 'message' => "Plan Connection is currently down contact admin"]);
        }
    
        //validate decoder number
        if ($plan->cableType->apiVerificationConnection != null && $plan->cableType->order_delivery == "1") {
            $endpoint =  $plan->cableType->apiVerificationConnection->url;
    
            //header
            $Myheader = getHeaderApiConnection($plan->cableType->apiVerificationConnection);
            $header = $Myheader["header"];
            $token = $Myheader["token"];
    
            if ($plan->cableType->apiVerificationConnection->method == "1") {
    
                //payload
                $apiPayLoad = json_decode($plan->cableType->apiVerificationConnection->payload, true);
                $payload = array();
                if ($apiPayLoad == null ||  !is_array($apiPayLoad)) {
                    return response(['success' => false, 'message' => "Service Connection playload not active contact admin"]);
                } else {
                    foreach ($apiPayLoad as $key => $value) {
                        $value = str_replace("{{decoderNumber}}", $request->decoder_number, $value);
                        $value = str_replace("{{tvName}}", $apiTv, $value);
                        $value = str_replace("{{tvNameLower}}", strtolower($apiTv), $value);
                        $value = str_replace("{{cableName}}", $apiTv, $value);
                        $value = str_replace("{{cableCode}}", $plan->cableType->code, $value);
                        $value = str_replace("{{phoneNumber}}", $request->phone_number, $value);
                        $value = str_replace("{{planId}}", $plan->id, $value);
                        $value = str_replace("{{cableId}}", $plan->cableType->id, $value);
                        $value = str_replace("{{code}}", $plan->code, $value);
                        $value = str_replace("{{planCode}}", $plan->plan_code, $value);
                        $value = str_replace("{{current_date}}", date("Ymdhis"), $value);
                        $payload[$key] = $value;
                    }
                }
                //$json = postHttp($payload, $endpoint, $header);
                if ($plan->cableType->apiVerificationConnection->method == "1") {
                    $json = postHttp($payload, $endpoint, $header);
                } else {
                    $json = postHttpParameter($endpoint, $payload, $header);
                }
            } else {
                $endpoint = str_replace("{{decoderNumber}}", $request->decoder_number, $endpoint);
                $endpoint = str_replace("{{tvName}}", $apiTv, $endpoint);
                $endpoint = str_replace("{{tvNameLower}}", strtolower($apiTv), $endpoint);
                $endpoint = str_replace("{{cableName}}", $apiTv, $endpoint);
                $endpoint = str_replace("{{cableCode}}", $plan->cableType->code, $endpoint);
                $endpoint = str_replace("{{phoneNumber}}", $request->phone_number, $endpoint);
                $endpoint = str_replace("{{planId}}", $plan->id, $endpoint);
                $endpoint = str_replace("{{code}}", $plan->code, $endpoint);
                $endpoint = str_replace("{{planCode}}", $plan->plan_code, $endpoint);
                $endpoint = str_replace("{{cableId}}", $plan->cableType->id, $endpoint);
                $endpoint = str_replace("{{token}}", $token, $endpoint);
                $endpoint = str_replace("{{current_date}}", date("Ymdhis"), $endpoint);
    
                $json = getHttp($endpoint, $header);
            }
            $apiPassResponse = false;
            $apiSuccessField = $plan->cableType->apiVerificationConnection->success_field;
            $apiSuccessResponse = $plan->cableType->apiVerificationConnection->success_response;
    
            try {
                if ($apiSuccessResponse == "not null") {
                    if ($json != null) {
                        $areponseVal = $json;
                        foreach (explode('->', $apiSuccessField) as $itemR) {
                            $areponseVal = $areponseVal->$itemR;
                        }
                        if (!empty($areponseVal)) {
    
                            $apiPassResponse = true;
                        }
                    }
                } else {
                    if ($json != null) {
                        $areponseVal = $json;
                        foreach (explode('->', $apiSuccessField) as $itemR) {
                            $areponseVal = $areponseVal->$itemR;
                        }
                        if ($areponseVal == $apiSuccessResponse) {
    
                            $apiPassResponse = true;
                        }
                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
    
            if (!$apiPassResponse) {
                return response(['success' => false, 'message' => "The decoder number is not associated with any multichoice user. Please recheck the number and retry",]);
            }
        }
        //end of decoder number validation
    
        // $userTransaction = $user->transaction()->latest()->first();
        // if ($userTransaction) {
        //     if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  $plan->cableType->name) {
        //         //$tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
        //         return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);
        //     }
        // }
    
        $amount = $plan->amount;
         //check offer
        $checkOffer = Offer::where("service_type", "CABLE")->where("plan_id", $plan->id)->where("user_level_id", $user->user_level_id)
        ->where("status", "1")->first();
        if($checkOffer){
        $amount = $checkOffer->amount;
        }
    
        //check user Wallet
        $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }
    
        $finalStatus = "pending";
    
    
    
        $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
        $init_balance = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];
    
    
    
        if ($balanceAction["success"]) {
            $description =  $plan->cableType->name . " " . $plan->name . " to $request->decoder_number";
            $Trx = strtoupper(Str::random(16));
            $ul['wallet_id'] = $select_wallet_id;
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = $plan->cableType->name;
            $ul['api_connection_id'] = $plan->cableType->apiConnection->id;
            $ul['service_type_id'] = $plan->cableType->id;
            $ul['service_plan_id'] = $plan->id;
            $ul['service_type'] = "Cable Subscription";
            $ul['description'] = $description;
            $ul['amount'] = $amount;
            $ul['real_price'] = $plan->real_price;
            $ul['charge'] = $amount - $plan->real_price;
            $ul['phone_number'] = $request->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $Trx;
            $ul['status'] = $finalStatus;
            $tansaction =  $user->transaction()->create($ul);
        } else {
            return response(['success' => false, 'message' => $balanceAction["message"] ?? "Wallet balance error"]);
        }
        $sucess = false;
        $isPending = false; // true when VTpass reports the txn is still processing
    
        //if delivery is manual
        if ($plan->cableType->order_delivery == "0") {
            if ($tansaction) {
                $message = "{$user->profile->username} wants to pay for cable subsctioption on {$_SERVER['SERVER_NAME']} <br/> $plan->cableType->name $plan->name to $request->decoder_number <br/>Reference Number: $Trx";
                try {
                    $this->sendMail(getSiteSettings("email"), "Admin", 'Bill Payment', $message);
                } catch (\Throwable $th) {
                    //throw $th;
                }
                $TranxMessage = "Order will be delivered shortly thank you";
                $sucess = true;
            }
        } else {
            //automated delivery
            $finalStatus = "success";
            if ($plan->cableType->apiConnection != null) {
                $apiSuccessField = $plan->cableType->apiConnection->success_field;
                $apiSuccessResponse = $plan->cableType->apiConnection->success_response;
                $endpoint = $plan->cableType->apiConnection->url;
    
                //header
                $Myheader = getHeaderApiConnection($plan->cableType->apiConnection);
                $header = $Myheader["header"];
                $token = $Myheader["token"];
                $json = null;
                if ($plan->cableType->apiConnection->method == "0") {
                    //get
                    $endpoint = str_replace("{{decoderNumber}}", $request->decoder_number, $endpoint);
                    $endpoint = str_replace("{{tvName}}", $apiTv, $endpoint);
                    $endpoint = str_replace("{{tvNameLower}}", strtolower($apiTv), $endpoint);
                    $endpoint = str_replace("{{cableName}}", $apiTv, $endpoint);
                    $endpoint = str_replace("{{phoneNumber}}", $request->phone_number, $endpoint);
                    $endpoint = str_replace("{{planId}}", $plan->id, $endpoint);
                    $endpoint = str_replace("{{planCode}}", $plan->plan_code, $endpoint);
                    $endpoint = str_replace("{{cableCode}}", $plan->cableType->code, $endpoint);
                    $endpoint = str_replace("{{referenceNumber}}", $Trx, $endpoint);
                    $endpoint = str_replace("{{token}}", $token, $endpoint);
                    $endpoint = str_replace("{{cableId}}", $plan->cableType->id, $endpoint);
                    $endpoint = str_replace("{{current_date}}", date("Ymdhis"), $endpoint);
                    $json = getHttp($endpoint, $header);
                } else {
                    //payload
                    $apiPayLoad = json_decode($plan->cableType->apiConnection->payload, true);
                    $payload = array();
                    if ($apiPayLoad == null ||  !is_array($apiPayLoad)) {
                        return response(['success' => false, 'message' => "Service Connection payload not active contact admin"]);
                    } else {
                        foreach ($apiPayLoad as $key => $value) {
                            $value = str_replace("{{decoderNumber}}", $request->decoder_number, $value);
                            $value = str_replace("{{tvName}}", $apiTv, $value);
                            $value = str_replace("{{tvNameLower}}", strtolower($apiTv), $value);
                            $value = str_replace("{{cableName}}", $apiTv, $value);
                            $value = str_replace("{{phoneNumber}}", $request->phone_number, $value);
                            $value = str_replace("{{planId}}", $plan->id, $value);
                            $value = str_replace("{{planCode}}", $plan->plan_code, $value);
                            $value = str_replace("{{cableCode}}", $plan->cableType->code, $value);
                            $value = str_replace("{{referenceNumber}}", $Trx, $value);
                            $value = str_replace("{{token}}", $token, $value);
                            $value = str_replace("{{cableId}}", $plan->cableType->id, $value);
                            $value = str_replace("{{current_date}}", date("Ymdhis"), $value);
                            $payload[$key] = $value;
                        }
                    }
                    if ($plan->cableType->apiConnection->method == "1") {
                        $json = postHttp($payload, $endpoint, $header);
                    } else {
                        $json = postHttpParameter($endpoint, $payload, $header);
                    }
                    //$json = postHttp($payload, $endpoint, $header);
                }
    
                if ($json != null) {
                    $reponseVal = $json;
                    foreach (explode('->', $apiSuccessField) as $itemS) {
                        $reponseVal = $reponseVal->$itemS;
                    }
                    if ($reponseVal == $apiSuccessResponse) {
                        $sucess = true;
                    } else {
                        $sucess = false;
                    }
                }

                // --- Detect a VTpass "still processing" response ---
                // (see BillController for the full explanation) — pending must
                // never be refunded; leave it pending to settle via requery.
                if (!$sucess && $json != null) {
                    $isPending = isVtpassPending($json);
                }
            }
        } //end of automated delivery
    
        //check if success and Update Record
        if ($sucess) {
            referralCommission($user, $tansaction, $amount);
            
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus, 'message' => $TranxMessage]);
            if ($query) {
                // SMS Message
                $smsDescription = "Txn: Debit\n" .
                       "Amt: NGN " . number_format($amount, 2) . "\n" .
                       "Des: CABLE SUB {$plan->cableType->name} {$plan->name} {$request->decoder_number} {$Trx}\n" .
                       "Date: " . now()->format('d-m-Y H:i') . "\n" .
                       "Bal: NGN " . number_format($final_balance, 2);
    
                $description =  $plan->cableType->name . " " . $plan->name . " to $request->decoder_number $TranxMessage Reference ID Is : #$Trx";
                
                if (getSiteSettings("email_notify") == "Yes") {
                    $maskedDecoder = substr($request->decoder_number, 0, 4) . '***' . substr($request->decoder_number, -3);
                    $formattedDate = now()->format('d-M-Y h:i A');
                    $formattedAmount = number_format($amount, 2);
                    $formattedBalance = number_format($final_balance, 2);
                    $cableProvider = $plan->cableType->name; // e.g., DSTV, GOTV
                    $planName = $plan->name;
                    $transactionRef = $Trx;
                    $transactionType = "Cable Subscription";
                    
                    $message = "
                        <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                        <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                                <td style='padding:8px; border:1px solid #ddd;'>DEBIT (Cable Subscription)</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Cable Provider</td>
                                <td style='padding:8px; border:1px solid #ddd;'><strong>{$cableProvider}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Plan</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$planName}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Decoder Number</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$maskedDecoder}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Subscription Amount</td>
                                <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Currency</td>
                                <td style='padding:8px; border:1px solid #ddd;'>NGN</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$transactionRef}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Description</td>
                                <td style='padding:8px; border:1px solid #ddd;'>Cable subscription of ₦{$formattedAmount} on {$cableProvider} - {$planName}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Date and Time</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Available Balance</td>
                                <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedBalance}</td>
                            </tr>
                        </table>
                        <br>
                        <p>Thank you for using Dupay.</p>
                    ";
                    
                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification', $message);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
    
                    (new FirebaseUserUpdate)->addUserNotification($user,
                    [
                        "message" => $description,
                        "type" => "transaction",
                        "read" => 0,
                        "data" => [
                            "service" => 'Cable Subscription',
                            "id" => $tansaction->id,
                            "reference_id" => $Trx,
                            "status" => $finalStatus,
                            'date' => $date
                        ]
                    ]
                    );
                }
                
                if (getSiteSettings("phone_notify") == "Yes") {
                    try {
                        // Send SMS with the formatted cable subscription message
                        sendTransactionSms($user, $smsDescription, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                
                //update firebase
                (new FirebaseUserUpdate)->updateUser($user);
                //check referral commission
            //iSmartBonus::referralCommission($user, $tansaction);
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'successful',
                    'data' => [
                        'service' => "Cable Subscription",
                        'Tv' => $plan->cableType->name,
                        'plan' => $plan->name,
                        'amount' => $amount,
                        'date' => $date,
                        'message' => $TranxMessage ?? $description,
                        'reference' => $Trx,
                        'wallet_balance' => $final_balance,
                    ],
                    'user' => $user,
                ]);
            }
        } else {

            // Keep as pending (no refund) when VTpass is still processing, the
            // connection isn't set to auto-refund, or we got no response — never
            // refund blind on a txn that may still be going through.
            if($isPending || $plan->cableType->apiConnection->auto_refund != "1" || $json == null){
                return response()->json([
                    'success' => true,
                    'status' => 'Pending',
                    'message' => 'Transaction Processing',
                    'data' => [
                        'reference' => $Trx,
                        'date' => $date
                    ],
                ]);
            }
    
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful", 'message' => null]);
            if ($query) {
                //refund user
                $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
                $init_balance = $balanceAction["init_balance"];
                $final_balance = $balanceAction["final_balance"];
                if ($balanceAction["success"]) {
                    $description =  $plan->cableType->name . " " . $plan->name . " to $request->decoder_number Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['service'] = $plan->cableType->name;
                    $ul['api_connection_id'] = $plan->cableType->apiConnection->id;
                    $ul['service_type_id'] = $plan->cableType->id;
                    $ul['service_plan_id'] = $plan->id;
                    $ul['service_type'] = "Cable Subscription";
                    $ul['description'] = $description;
                    $ul['amount'] = $amount;
                    $ul['phone_number'] = $request->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $init_balance;
                    $ul['final_balance'] = $final_balance;
                    $ul['reference'] = $trx;
                    $ul['reference_ref'] = $Trx;
                    $ul['status'] = "refund";
                    $tansaction =  $user->transaction()->create($ul);
                    (new FirebaseUserUpdate)->addUserNotification($user,
                    [
                        "message" => $description,
                        "type" => "transaction",
                        "read" => 0,
                        "data" => [
                            "service" => 'Cable Subscription',
                            "id" => $tansaction->id,
                            "reference_id" => $trx,
                            "status" => "refund",
                            'date' => $date
                        ]
                    ]
                    );
                }
            }
            //update firebase
            (new FirebaseUserUpdate)->updateUser($user);
    
            return response()->json([
                'success' => false,
                'status' => 'unsuccessful',
                'message' => 'Transaction unsuccessful',
    
                'data' => [
                    'service' => "Cable Subscription",
                    'Tv' => $plan->cableType->name,
                    'plan' => $plan->name,
                    'amount' => $amount,
                    'message' => $TranxMessage ?? $description,
                    'reference' => $Trx,
                    'date' => $date,
                    'wallet_balance' => $final_balance,
                ],
                'user' => $user,
            ]);
        }
    }
}