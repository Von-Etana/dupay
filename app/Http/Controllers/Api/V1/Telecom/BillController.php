<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Bill;
use App\Models\Telecom\Offer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use iSmartBonus;

class BillController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }


     //get all bill list
     public function getBillList()
     {
         $item = Bill::where("status", "1")
             ->get();
         if (count($item) < 1) {
             return response(['success' => false, 'message' => "No available bill"]);
         }

         return response(['success' => true, "data" => $item]);
     }


    public function verifyBillNumber(Request $request){
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'disco_id' => 'required',
            'meter_number' => 'required',
            'meter_type' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }



        $bill = Bill::find($request->disco_id);
        $Trx = strtoupper(Str::random(16));

        if (!$bill) {
            return response(['success' => false, 'message' => "Bill Does not Exist"]);
        }

        if ($bill->status == "0" ) {
            return response(['success' => false, 'message' => "Bill Is Currently not Available try again later"]);
        }

        if (($bill->apiConnection == null || $bill->apiConnection->status == "0")) {
            return response(['success' => false, 'message' => "Bill Connection is down contact admin"]);
        }

        if (($bill->apiVerificationConnection == null || $bill->apiVerificationConnection->status == "0")) {
            return response(['success' => false, 'message' => "Bill Verification Connection is down contact admin"]);
        }

        //validate meter number
        if($bill->apiVerificationConnection != null && $bill->order_delivery == "1"){
            $endpoint =  $bill->apiVerificationConnection->url;
             //header
                 $Myheader = getHeaderApiConnection($bill->apiVerificationConnection);
                 $header = $Myheader["header"];
                 $token = $Myheader["token"];

            if($bill->apiVerificationConnection->method == "1"){
            //payload
            $apiPayLoad = json_decode($bill->apiVerificationConnection->payload, true);
            $payload = array();
            if($apiPayLoad == null ||  !is_array($apiPayLoad)){
                return response(['success' => false, 'message' => "Verification Service Connection playload not active contact admin"]);
            }else{
                foreach ($apiPayLoad as $key => $value) {
                    $value = str_replace("{{discoId}}",$bill->id,$value);
                    $value = str_replace("{{disco}}",$bill->disco,$value);
                    $value = str_replace("{{discoName}}",$bill->name,$value);
                    $value = str_replace("{{discoCode}}",$bill->code,$value);
                    $value = str_replace("{{variationCode}}",$bill->variation_code,$value);
                    $value = str_replace("{{meterNumber}}",$request->meter_number,$value);
                    $value = str_replace("{{phoneNumber}}",$request->phone_number,$value);
                    $value = str_replace("{{meterType}}",$request->meter_type,$value);
                    $value = str_replace("{{amount}}",$request->amount,$value);
                    $value = str_replace("{{referenceNumber}}",$Trx,$value);
                    $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                    $payload[$key] = $value;
                }
            }
                $json = postHttp($payload, $endpoint, $header);
            }else{
                $endpoint = str_replace("{{discoId}}",$bill->id,$endpoint);
                $endpoint = str_replace("{{disco}}",$bill->disco,$endpoint);
                $endpoint = str_replace("{{discoName}}",$bill->name,$endpoint);
                $endpoint = str_replace("{{discoCode}}",$bill->code,$endpoint);
                $endpoint = str_replace("{{variationCode}}",$bill->variation_code,$endpoint);
                $endpoint = str_replace("{{meterNumber}}",$request->meter_number,$endpoint);
                $endpoint = str_replace("{{phoneNumber}}",$request->phone_number,$endpoint);
                $endpoint = str_replace("{{meterType}}",$request->meter_type,$endpoint);
                $endpoint = str_replace("{{amount}}",$request->amount,$endpoint);
                $endpoint = str_replace("{{referenceNumber}}",$Trx,$endpoint);
                $endpoint = str_replace("{{token}}",$token,$endpoint);
                $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
                $json = getHttp($endpoint, $header);
            }
                //return response(['success' => false, 'message' => $json]);
                $customerName = null;
                $apiPassResponse = false;
                $apiSuccessField = $bill->apiVerificationConnection->success_field;
                $apiSuccessResponse = $bill->apiVerificationConnection->success_response;

            try {
                if($apiSuccessResponse == "not null"){
                    if ($json != null){
                        $areponseVal = $json;
                        foreach(explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                        }
                        if(!empty($areponseVal)){
                            $customerName = $areponseVal;
                            $apiPassResponse = true;
                        }
                    }
                }else{
                    if($json != null){
                        $areponseVal = $json;
                        foreach(explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                        }
                        if($areponseVal == $apiSuccessResponse){
                            $customerName = $areponseVal;
                            $apiPassResponse = true;
                        }

                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            if(!$apiPassResponse){
                return response(['success' => false, 'message' => "The meter number you supplied is not found under the selected disco and meter type. Please recheck the number and retry",]);
            }

            return response(['success' => true, 'message' => $customerName]);

        }

    }






    public function buyBill(Request $request){
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'phone_number' => 'required',
            'disco_id' => 'required',
            'meter_number' => 'required',
            'meter_type' => 'required',
            'amount' => 'required',
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
        if (!checkWalletEnable("Bill", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }
    
        if (request("amount") < 500) {
            return response(['success' => false, 'message' => "BELOW MINIMUM AMOUNT ALLOWED"]);
        }
    
        $bill = Bill::find($request->disco_id);
        $Trx = strtoupper(Str::random(16));
    
        if (!$bill) {
            return response(['success' => false, 'message' => "Bill Does not Exist"]);
        }
    
        if ($bill->status == "0" ) {
            return response(['success' => false, 'message' => "Bill Is Currently not Available try again later"]);
        }
    
        if (($bill->apiConnection == null || $bill->apiConnection->status == "0") && $bill->order_delivery == "1") {
            return response(['success' => false, 'message' => "Bill Connection is down contact admin"]);
        }
    
         //validate meter number
         if($bill->apiVerificationConnection != null && $bill->order_delivery == "1"){
            $endpoint =  $bill->apiVerificationConnection->url;
             //header
                 $Myheader = getHeaderApiConnection($bill->apiVerificationConnection);
                 $header = $Myheader["header"];
                 $token = $Myheader["token"];
    
            if($bill->apiVerificationConnection->method == "1"){
            //payload
            $apiPayLoad = json_decode($bill->apiVerificationConnection->payload, true);
            $payload = array();
            if($apiPayLoad == null ||  !is_array($apiPayLoad)){
                return response(['success' => false, 'message' => "Verification Service Connection payload not active contact admin"]);
            }else{
                foreach ($apiPayLoad as $key => $value) {
                    $value = str_replace("{{discoId}}",$bill->id,$value);
                    $value = str_replace("{{disco}}",$bill->disco,$value);
                    $value = str_replace("{{discoName}}",$bill->name,$value);
                    $value = str_replace("{{discoCode}}",$bill->code,$value);
                    $value = str_replace("{{variationCode}}",$bill->variation_code,$value);
                    $value = str_replace("{{meterNumber}}",$request->meter_number,$value);
                    $value = str_replace("{{phoneNumber}}",$request->phone_number,$value);
                    $value = str_replace("{{meterType}}",$request->meter_type,$value);
                    $value = str_replace("{{amount}}",$request->amount,$value);
                    $value = str_replace("{{referenceNumber}}",$Trx,$value);
                    $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                    $payload[$key] = $value;
                }
            }
                //$json = postHttp($payload, $endpoint, $header);
                if($bill->apiVerificationConnection->method == "1"){
                    $json = postHttp($payload, $endpoint, $header);
                }else{
                    $json = postHttpParameter($endpoint, $payload, $header);
                }
            }else{
                $endpoint = str_replace("{{discoId}}",$bill->id,$endpoint);
                $endpoint = str_replace("{{disco}}",$bill->disco,$endpoint);
                $endpoint = str_replace("{{discoName}}",$bill->name,$endpoint);
                $endpoint = str_replace("{{discoCode}}",$bill->code,$endpoint);
                $endpoint = str_replace("{{variationCode}}",$bill->variation_code,$endpoint);
                $endpoint = str_replace("{{meterNumber}}",$request->meter_number,$endpoint);
                $endpoint = str_replace("{{phoneNumber}}",$request->phone_number,$endpoint);
                $endpoint = str_replace("{{meterType}}",$request->meter_type,$endpoint);
                $endpoint = str_replace("{{amount}}",$request->amount,$endpoint);
                $endpoint = str_replace("{{referenceNumber}}",$Trx,$endpoint);
                $endpoint = str_replace("{{token}}",$token,$endpoint);
                $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
                $json = getHttp($endpoint, $header);
            }
    
            $apiPassResponse = false;
            $apiSuccessField = $bill->apiVerificationConnection->success_field;
            $apiSuccessResponse = $bill->apiVerificationConnection->success_response;
            try {
                if($apiSuccessResponse == "not null"){
                    if ($json != null){
                        $areponseVal = $json;
                        foreach(explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                        }
                        if(!empty($areponseVal)){
                            $apiPassResponse = true;
                        }
    
                    }
                }else{
                    if($json != null){
                        $areponseVal = $json;
                        foreach(explode('->', $apiSuccessField) as $itemR) {
                        $areponseVal = $areponseVal->$itemR;
                        }
                        if($areponseVal == $apiSuccessResponse){
                            $apiPassResponse = true;
                        }
    
                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
    
            if(!$apiPassResponse){
                return response(['success' => false, 'message' => "The meter number you supplied is not found under the selected disco and meter type. Please recheck the number and retry",]);
            }
    
        }
    //end of decoder number validation
    
        // $userTransaction = $user->transaction()->latest()->first();
        //     if($userTransaction){
        //         if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  $bill->disco)
        //         {
        //             //$tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
        //             return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);
    
        //         }
        //     }
    
        //check user Wallet
        // $rate =  $bill->price_rate/100 * $request->amount;
        // $amount = $request->amount - $rate;
        $amount = $bill->calLevelAmount($request->amount);
    
        //check offer
        $checkOffer = Offer::where("service_type", "BILL")->where("plan_id", $bill->id)
        ->where("user_level_id", $user->user_level_id)
        ->where("status", "1")->first();
        if($checkOffer){
           $amount = $request->amount - ($checkOffer->amount/100 * $request->amount);
        }
    
        $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }
    
        $TranxMessage = null;
        $finalStatus = "pending";
        // $wallet = $user->balance;
        // $balance = $wallet - $amount;
    
            $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
            $init_balance = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
    
        if ($balanceAction["success"]) {
            $description = "$bill->disco $request->meter_type to $request->meter_number";
            $Trx = strtoupper(Str::random(16));
            $ul['wallet_id'] = $select_wallet_id;
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = $bill->disco;
            $ul['service_type'] = "Bill Payment";
            $ul['api_connection_id'] = $bill->apiConnection->id;
            $ul['service_type_id'] = $bill->id;
            $ul['description'] = $description;
            $ul['amount'] = $amount;
            $ul['real_price'] = $bill->real_price;
            
            $ul['charge'] = (float)$amount - (float)$bill->real_price;
             
            $ul["message"] = $TranxMessage;
            $ul['phone_number'] = $request->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $Trx;
            $ul['status'] = $finalStatus;
            $tansaction =  $user->transaction()->create($ul);
        }else{
            return response(['success' => false, 'message' => $balanceAction["message"] ?? "Wallet balance error"]);
        }
        $token_unit = "null";
        $sucess = false;
        $isPending = false; // true when VTpass reports the txn is still processing
    
        //if delivery is manual
        if($bill->order_delivery == "0"){
            if($tansaction){
            $message = "{$user->profile->username} wants to pay for electricity on ".$_SERVER['SERVER_NAME']."<br/>Disco: $bill->disco<br/>Type: $request->meter_type<br/>Amount: $request->amount<br/>Meter Number: $request->meter_number<br/>Reference Number: $Trx";
            try {
                $this->sendMail(getSiteSettings("email"), "Admin", 'Bill Payment', $message);
            } catch (\Throwable $th) {
                //throw $th;
            }
            $TranxMessage = "Order will be delivered shortly thank you";
            $sucess = true;
        }
        }else{
    
        //automated delivery
        $finalStatus = "success";
        $billToken = "";
        if($bill->apiConnection != null){
    
                $apiSuccessField = $bill->apiConnection->success_field;
                $apiSuccessResponse = $bill->apiConnection->success_response;
                $endpoint = $bill->apiConnection->url;
    
                //header
                $Myheader = getHeaderApiConnection($bill->apiConnection);
                $header = $Myheader["header"];
                $token = $Myheader["token"];
    
                if($bill->apiConnection->method == "1"){
                    //payload
                    $apiPayLoad = json_decode($bill->apiConnection->payload, true);
                    $payload = array();
                    if($apiPayLoad == null ||  !is_array($apiPayLoad)){
                        return response(['success' => false, 'message' => "Service Connection payload not active contact admin"]);
                    }else{
                        foreach ($apiPayLoad as $key => $value) {
                            $value = str_replace("{{discoId}}",$bill->id,$value);
                            $value = str_replace("{{disco}}",$bill->disco,$value);
                            $value = str_replace("{{discoName}}",$bill->name,$value);
                            $value = str_replace("{{discoCode}}",$bill->code,$value);
                            $value = str_replace("{{variationCode}}",$bill->variation_code,$value);
                            $value = str_replace("{{meterNumber}}",$request->meter_number,$value);
                            $value = str_replace("{{phoneNumber}}",$request->phone_number,$value);
                            $value = str_replace("{{meterType}}",$request->meter_type,$value);
                            $value = str_replace("{{amount}}",$request->amount,$value);
                            $value = str_replace("{{referenceNumber}}",$Trx,$value);
                            $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                            $payload[$key] = $value;
                        }
                    }
                        //$json = postHttp($payload, $endpoint, $header);
    
                        if($bill->apiConnection->method == "1"){
                            $json = postHttp($payload, $endpoint, $header);
                        }else{
                            $json = postHttpParameter($endpoint, $payload, $header);
                        }
    
                    }else{
                        $endpoint = str_replace("{{discoId}}",$bill->id,$endpoint);
                        $endpoint = str_replace("{{disco}}",$bill->disco,$endpoint);
                        $endpoint = str_replace("{{discoName}}",$bill->name,$endpoint);
                        $endpoint = str_replace("{{discoCode}}",$bill->code,$endpoint);
                        $endpoint = str_replace("{{variationCode}}",$bill->variation_code,$endpoint);
                        $endpoint = str_replace("{{meterNumber}}",$request->meter_number,$endpoint);
                        $endpoint = str_replace("{{phoneNumber}}",$request->phone_number,$endpoint);
                        $endpoint = str_replace("{{meterType}}",$request->meter_type,$endpoint);
                        $endpoint = str_replace("{{amount}}",$request->amount,$endpoint);
                        $endpoint = str_replace("{{referenceNumber}}",$Trx,$endpoint);
                        $endpoint = str_replace("{{token}}",$token,$endpoint);
                        $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
                        $json = getHttp($endpoint, $header);
                    }
    
                    if($apiSuccessResponse == "not null"){
                        if ($json != null){
                            $reponseVal = $json;
                            foreach(explode('->', $apiSuccessField) as $itemS) {
                            $reponseVal = $reponseVal->$itemS;
                            }
                            if(!empty($reponseVal)){
                                $sucess = true;
                                try {
                                    //if ($request->meter_type == "PREPAID") {
                                        $token_unit = "Token: {$reponseVal}";
                                        $TranxMessage = $token_unit;
                                   // }
                                } catch (\Throwable $th) {
                                    //throw $th;
                                }
                            }
    
                        }
                    }else{
                    if ($json != null) {
                        $reponseVal = $json;
                        foreach(explode('->', $apiSuccessField) as $itemS) {
                        $reponseVal = $reponseVal->$itemS;
                        }
                        if ($reponseVal == $apiSuccessResponse) {
                            $sucess = true;
                                $billToken = $description." Token ".$json->token;
                        } else {
                            $sucess = false;
                        }
                    }
                }

                // --- Detect a VTpass "still processing" response ---
                // VTpass often returns the transaction as pending/initiated first,
                // then settles to delivered moments later. In that case we must NOT
                // refund or mark unsuccessful — we leave it pending so a requery
                // (or VTpass's webhook) can finalize it. Only a genuine, confirmed
                // failure should trigger a refund.
                if (!$sucess && $json != null) {
                    $isPending = isVtpassPending($json);
                }
        }
    }//end of automated delivery
    
        //check if success and Update Record
        if ($sucess) {
            referralCommission($user, $tansaction, $amount);
            
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus, 'message' => $TranxMessage, 'description' => $billToken ?? 0]);
            if ($query) {
                // SMS Message
                $smsDescription = "Txn: Debit\n" .
                       "Amt: NGN " . number_format($amount, 2) . "\n" .
                       "Des: ELEC BILL {$bill->disco} {$request->meter_type} {$request->meter_number} {$Trx}\n" .
                       "Date: " . now()->format('d-m-Y H:i') . "\n" .
                       "Bal: NGN " . number_format($final_balance, 2);
    
                $description = "$bill->disco $request->meter_type to $request->meter_number $TranxMessage Reference ID Is : #$Trx";
                
                if (getSiteSettings("email_notify") == "Yes") {
                    $maskedMeter = substr($request->meter_number, 0, 4) . '***' . substr($request->meter_number, -3);
                    $formattedDate = now()->format('d-M-Y h:i A');
                    $formattedAmount = number_format($amount, 2);
                    $formattedBalance = number_format($final_balance, 2);
                    $discoName = $bill->disco;
                    $meterType = $request->meter_type;
                    $transactionRef = $Trx;
                    $transactionType = "Electricity Bill Payment";
                    
                    $message = "
                        <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                        <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                                <td style='padding:8px; border:1px solid #ddd;'>DEBIT (Electricity Bill)</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Disco Provider</td>
                                <td style='padding:8px; border:1px solid #ddd;'><strong>{$discoName}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Meter Type</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$meterType}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Meter Number</td>
                                <td style='padding:8px; border:1px solid #ddd;'>{$maskedMeter}</td>
                            </tr>
                            <tr>
                                <td style='padding:8px; border:1px solid #ddd;'>Bill Amount</td>
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
                                <td style='padding:8px; border:1px solid #ddd;'>Electricity bill payment of ₦{$formattedAmount} on {$discoName} - {$meterType} Meter</td>
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
                            "service" => 'Bill Payment',
                            "id" => $tansaction->id,
                            "reference_id" => $Trx,
                            "status" => $finalStatus,
                            'date' => $date
                        ]
                    ]
                    );
                }
                
                if (getSiteSettings("phone_notify") == "Yes" && $user->phone_number != null) {
                    try {
                        // Send SMS with the formatted electricity bill message
                        sendTransactionSms($user, $smsDescription, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                
                //update firebase
                (new FirebaseUserUpdate)->updateUser($user);
                //check referral commission
                iSmartBonus::referralCommission($user, $tansaction);
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'successful',
                    'data' => [
                        'service' => "Bill Payment",
                        'bill' => $bill->name,
                        'amount' => $request->amount,
                        'date' => $date,
                        'reference' => $Trx,
                        'message' => $billToken ?? 0,
                        'wallet_balance' => $final_balance,
                    ],
                ]);
            }
        } else {

            // Keep as pending (no refund) when EITHER:
            //   - VTpass reported the txn is still processing ($isPending), OR
            //   - this connection isn't set to auto-refund, OR
            //   - we got no response at all ($json == null) — never refund blind,
            //     since the txn may actually be going through on VTpass's side.
            // A blind refund here is exactly what caused "debited, shows pending,
            // then later refunded + unsuccessful" on a single successful request.
            if($isPending || $bill->apiConnection->auto_refund != "1" || $json == null){
                //update firebase
                (new FirebaseUserUpdate)->updateUser($user);
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
                    $description = "$bill->disco $request->meter_type to $request->meter_number Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['service'] = $bill->disco;
                    $ul['api_connection_id'] = $bill->apiConnection->id;
                    $ul['service_type_id'] = $bill->id;
                    $ul['service_type'] = "Bill Payment";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
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
                            "service" => 'Bill Payment',
                            "id" => $tansaction->id,
                            "reference_id" => $Trx,
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
                    'reference' => $Trx,
                    'date' => $date
                ],
            ]);
        }
    }


}