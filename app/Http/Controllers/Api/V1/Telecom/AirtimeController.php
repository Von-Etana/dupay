<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Airtime;
use App\Models\Telecom\AirtimeToCash;
use App\Models\Telecom\Network;
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

class AirtimeController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    /**********************
     *      iSMart Olamilekan
     *         07038858824 
     *
     *  Buy Airtime
     *
     *
     */
    public function getAirtimeNetwork()
    {
        $item = Network::where("status", "1")
            ->with("airtimeType")
            ->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No available Network"]);
        }

        return response(['success' => true, "data" => $item]);
    }


    public function buyAirtime(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'phone_number' => 'required',
            'network_id' => 'required',
            'type_id' => '',
            'amount' => 'required',
        ]);
        $TranxMessage = null;

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();
        $finalStatus = "pending";
        
        
        
        
       
                
                
                

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

        if (request("amount") < 100) {
            return response(['success' => false, 'message' => "BELOW MINIMUM AMOUNT ALLOWED"]);
        }
        
        $limitCheck = checkDailyTransactionLimit($user, $request->amount);
        if (!$limitCheck['success']) {
            return response(['success' => false,
            'message' => $limitCheck['message']]);
        }


        $select_wallet_id = $request->wallet_id ?? "1";
        if (!checkWalletEnable("Airtime", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }

        $typId = $request->type_id ?? 1;
        $item = Airtime::where("network_id", $request->network_id)->where("airtime_type_id",$typId)->first();

        if (!$item) {
            return response(['success' => false, 'message' => "Airtime not available"]);
        }

        $network_name = $item->networks->name;
        $phone  = $request->phone_number;
        $validateNumber = substr($phone, 0, 4);

        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || $_SERVER['HTTP_ISMART'] != "app") {
        if($request->ported_number != "on"){

            if($network_name=="MTN"){
                if(strpos("0702 0703 0713 0704 0706 0716 0802 0803 0806 0810 0813 0814 0816 0903 0913 0906 0916 0804 ", $validateNumber) == FALSE || strlen($phone) != 11){
                    return response(['success' => false, 'message' => "This number $phone is not an $network_name Number"]);
                }
                }
                else if($network_name=="GLO"){
                if(strpos("0805 0705 0905 0807 0907 0707 0817 0917 0717 0715 0815 0915 0811 0711 0911 ", $validateNumber) == FALSE || strlen($phone) != 11){
                    return response(['success' => false, 'message' => "This number $phone is not a $network_name Number"]);
                }
                }
                else if($network_name=="AIRTEL"){
                if(strpos("0904 0802 0902 0702 0808 0908 0708 0918 0818 0718 0812 0912 0712 0801 0701 0901 0907 0917 ", $validateNumber) == FALSE || strlen($phone) != 11){
                    return response(['success' => false, 'message' => "This number $phone is not an $network_name Number"]);
                }
                }
                else if($network_name =="9MOBILE"){
                if(strpos("0809 0909 0709 0819 0919 0719 0817 0917 0717 0718 0918 0818 0808 0708 0908 ", $validateNumber) == FALSE || strlen($phone) != 11){
                    return response(['success' => false, 'message' => "This number $phone is not a $network_name Number"]);
                }
            }
        }
    }

        if ($item->networks->status == "0" || $item->airtimeType->status == "0" || $item->status == "0" || $item->airtimeType->apiConnection->status == "0") {
            return response(['success' => false, 'message' => "Airtime Is Currently not Available try again later"]);
        }

        if (($item->airtimeType->apiConnection == null || $item->airtimeType->apiConnection->status == "0") && $item->airtimeType->order_delivery == "1") {
            return response(['success' => false, 'message' => "Airtime Connection Currently down contact admin"]);
        }


        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api") {
    }

        if(request('airtimeBonus') && isset($_SERVER['HTTP_ISMART']) && $_SERVER['HTTP_ISMART']  == "app"){
           $bonusRes = iSmartBonus::airtimeBonus($user);
            if(!$bonusRes["success"]){
                return response(['success' => false, 'message' => $bonusRes["message"]]);
            }

        }else{

        $amount = $item->calLevelAmount($request->amount);


        //check offer
        $checkOffer = Offer::where("service_type", "AIRTIME")->where("plan_id", $item->id)->where("user_level_id", $user->user_level_id)
        ->where("status", "1")->first();
         if($checkOffer){
            $amount = $request->amount - ($checkOffer->amount/100 * $request->amount);
         }


        //check user Wallet
        $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }
        }

        $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
        $init_balance = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];

        $realPrice = $request->amount - ($item->real_price/100* $request->amount);

        if ($balanceAction["success"]) {
            $description = $item->networks->name . " $request->amount " . $item->airtimeType->name . " to $request->phone_number";
            $Trx = strtoupper(Str::random(16));
            $ul['wallet_id'] = $select_wallet_id;
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = $item->networks->name;
            $ul['api_connection_id'] = $item->airtimeType->apiConnection->id;
            $ul['service_type_id'] = $request->type_id;
            $ul['service_plan_id'] = $item->id;
            $ul['service_type'] = "Airtime Topup";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['real_price'] = $realPrice;
            $ul['charge'] = $amount - $realPrice;
            $ul['phone_number'] = $request->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $Trx;
            $ul['status'] = $finalStatus;
            $tansaction =  $user->transaction()->create($ul);
            
            
            
        }else{
            return response(['success' => false, 'message' => "Wallet balance error"]);
        }
        $sucess = false;


             //if delivery is manual
    if($item->airtimeType->order_delivery == "0"){
        if($tansaction){
        $message = "{$user->profile->username} wants to buy airtime on ".$_SERVER['SERVER_NAME']."<br/> {$item->networks->name}  {$item->airtimeType->name} $request->amount to $request->phone_number <br/>Reference Number: $Trx";
        try {
            $this->sendMail(getSiteSettings("email"), "Admin", 'Airtime Topup', $message);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $TranxMessage = "Order will be delivered shortly thank you";
        $sucess = true;
    }
    }else{
        $finalStatus = "success";
        try {

        $json = null;
        $apiSuccessField = $item->airtimeType->apiConnection->success_field;
        $apiSuccessResponse = $item->airtimeType->apiConnection->success_response;
        $endpoint = $item->airtimeType->apiConnection->url;

        //header
        $Myheader = getHeaderApiConnection($item->airtimeType->apiConnection);
        $header = $Myheader["header"];
        $token = $Myheader["token"];

        if ($item->airtimeType->apiConnection->method == "0") {
            //api Connection GET method
            $endpoint = str_replace("{{networkId}}",$item->networks->id,$endpoint);
            $endpoint = str_replace("{{networkCode}}",$item->networks->code,$endpoint);
            $endpoint = str_replace("{{networkName}}",$item->networks->name,$endpoint);
            $endpoint = str_replace("{{networkOtherName}}",$item->networks->other_name,$endpoint);
            $endpoint = str_replace("{{airtimeTypeId}}",$item->airtimeType->id,$endpoint);
            $endpoint = str_replace("{{airtimeTypeName}}",$item->airtimeType->name,$endpoint);
            $endpoint = str_replace("{{phoneNumber}}",$request->phone_number,$endpoint);
            $endpoint = str_replace("{{amount}}",$request->amount,$endpoint);
            $endpoint = str_replace("{{token}}",$token,$endpoint);
            $endpoint = str_replace("{{referenceNumber}}",$Trx,$endpoint);
            $endpoint = str_replace("{{current_date}}",date("Ymdhis"),$endpoint);
            $json = getHttp($endpoint, $header);

        } else {
            //APi Connection POST Method
        //payload
        $apiPayLoad = json_decode($item->airtimeType->apiConnection->payload, true);
            $payload = array();
        if($apiPayLoad == null ||  !is_array($apiPayLoad)){
            return response(['success' => false, 'message' => "Service Connection playload not active contact admin"]);
            }else{
            foreach ($apiPayLoad as $key => $value) {
                $value = str_replace("{{networkId}}",$item->networks->id,$value);
                $value = str_replace("{{networkCode}}",$item->networks->code,$value);
                $value = str_replace("{{networkName}}",$item->networks->name,$value);
                $value = str_replace("{{networkOtherName}}",$item->networks->other_name,$value);
                $value = str_replace("{{airtimeTypeId}}",$item->airtimeType->id,$value);
                $value = str_replace("{{airtimeTypeName}}",$item->airtimeType->name,$value);
                $value = str_replace("{{phoneNumber}}",$request->phone_number,$value);
                $value = str_replace("{{amount}}",$request->amount,$value);
                $value = str_replace("{{token}}",$token,$value);
                $value = str_replace("{{referenceNumber}}",$Trx,$value);
                $value = str_replace("{{current_date}}",date("Ymdhis"),$value);
                $payload[$key] = $value;
    }
}
            $json = postHttp($payload, $endpoint, $header);

        }

        if ($json != null) {
                    $reponseVal = $json;
                    foreach(explode('->', $apiSuccessField) as $itemS) {
                    $reponseVal = $reponseVal->$itemS;
                    }
            if ($reponseVal == $apiSuccessResponse) {
                $sucess = true;
            } else {
                $sucess = false;
            }
        }
        } catch (\Throwable $th) {
            //throw $th;
        }


    }//end of automatic order

        //check if success and Update Record
        if ($sucess) {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus, 'message' => $TranxMessage]);
            if ($query) {
                
                try {
                    referralCommission($user, $tansaction, $request->amount);
                } catch (\Throwable $th) {
                    \Log::error('Referral commission failed: '.$th->getMessage());
                }
                
                // $description = $item->networks->name . " $request->amount " . $item->airtimeType->name . " to $request->phone_number Reference ID Is : #$Trx";
                $description = "Txn: Debit\n" .
               "Amt: NGN " . number_format($request->amount, 2) . "\n" .
               "Des: MOB TOPUP {$request->phone_number} {$Trx}\n" .
               "Date: " . now()->format('d-m-Y H:i') . "\n" .
               "Bal: NGN " . number_format($final_balance, 2);
               
                    
                if (getSiteSettings("email_notify") == "Yes") {
                    
                        $maskedPhone = substr($request->phone_number, 0, 4) . '***' . substr($request->phone_number, -3);
                        $formattedDate = now()->format('d-M-Y h:i A');
                        $formattedAmount = number_format($request->amount, 2);
                        $formattedBalance = number_format($final_balance, 2);
                        $networkName = $item->networks->name; // e.g., MTN, GLO
                        $transactionRef = $Trx;
                        $transactionType = "Airtime Purchase";
                        
                        $message = "
                            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                            <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                                <tr>
                                    <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                                    <td style='padding:8px; border:1px solid #ddd;'>DEBIT (Airtime Purchase)</td>
                                </tr>
                                <tr>
                                    <td style='padding:8px; border:1px solid #ddd;'>Network</td>
                                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$networkName}</strong></td>
                                </tr>
                                <tr>
                                    <td style='padding:8px; border:1px solid #ddd;'>Phone Number</td>
                                    <td style='padding:8px; border:1px solid #ddd;'>{$maskedPhone}</td>
                                </tr>
                                <tr>
                                    <td style='padding:8px; border:1px solid #ddd;'>Airtime Amount</td>
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
                                    <td style='padding:8px; border:1px solid #ddd;'>Airtime purchase of ₦{$formattedAmount} on {$networkName}</td>
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
                    
                    try{
                        
                        $msg = $item->networks->name . " $request->amount " . $item->airtimeType->name . " to $request->phone_number Reference ID Is : #$Trx";
                        
                        sendFcmNotification($user->fcmToken, 'Airtime Topup', $msg, [
                            "service" => 'Airtime Topup',
                            "id" => $tansaction->id,
                            "reference_id" => $Trx,
                            "status" => $finalStatus,
                            'date' => $date
                        ]);
                    
                    }catch (\Throwable $th) {
                       \Log::info($th); 
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    try {
                        // $this->sendSms($user->phone_number, $description);
                        sendTransactionSms($user, $description, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                //update firebase
                (new FirebaseUserUpdate)->updateUser($user);
                 //check referral commission
                
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'successful',
                    'data' => [
                        'service' => "Airtime Topup",
                        'network' => $item->networks->name,
                        'Type' => $item->airtimeType->name,
                        'amount' => $request->amount,
                        'message' => $TranxMessage ?? $description,
                        'date' => $date,
                        'reference' => $Trx,
                        'wallet_balance' => $final_balance,
                    ],
                ]);
            }
        } else {
            //update firebase
            (new FirebaseUserUpdate)->updateUser($user);

            if($item->airtimeType->apiConnection->auto_refund != "1" || $json == null){
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
                (new FirebaseUserUpdate)->updateUser($user);

                if ($balanceAction["success"]) {
                    $description = $item->networks->name . " $request->amount " . $item->airtimeType->name . " to $request->phone_number Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['service'] = $item->networks->name;
                    $ul['api_connection_id'] = $item->airtimeType->apiConnection->id;
                    $ul['service_type_id'] = $item->airtimeType->id;
                    $ul['service_plan_id'] = $item->id;
                    $ul['service_type'] = "Airtime Topup";
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
                            "service" => 'Airtime Topup',
                            "id" => $tansaction->id,
                            "reference_id" => $Trx,
                            "status" => "refund",
                            'date' => $date
                        ]
                    ]
                    );
                }
            }
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



    /**********
     *
     *
     *
     *  Airtime to Cash
     *
     *
     */

    public function getAirtimeToCashNetwork()
    {
        $item = Network::where("status", "1")
            ->with("airtimeCashType")
            ->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No available Network"]);
        }

        return response(['success' => true, "data" => $item]);
    }

    public function convertAirtimeToCash(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'network' => 'required',
            'mode' => 'required',
            'amount' => 'required',
            'airtimeDetails' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();

        $item = AirtimeToCash::where("network_id", $request->network)->first();

        if (!$item) {
            return response(['success' => false, 'message' => "Airtime to cash is not available on this network"]);
        }

        if ($item->network->status == "0" || $item->status == "0" ) {
            return response(['success' => false, 'message' => "Airtime to Cash Is Currently not Available try again later"]);
        }

        if ($request->mode == "2" && strlen($request->airtimeDetails) != 11) {
            return response(['success' => false, 'message' => "Invalid Phone Number"]);
        }

        if ($request->amount < 499) {
            return response(['success' => false, 'message' => "Minimum airtime you can convert is 500 naira"]);
        }

        $amount_paid = $item->price_rate / 100 * $request->amount;
		$amount_paid = $request->amount - $amount_paid;
		$amount_paid = round($amount_paid);
        $amount_paid = $request->amount - $amount_paid;

        if ($request->mode == "2") {
            $description = "₦$request->amount {$item->network->name} airtime conversion -- from $request->airtimeDetails to $item->phone_number";
            $message = "Transaction logged. Send your airtime to $item->phone_number";
        } else if ($request->mode == "1") {
            $message = "Transaction logged. Please wait for confirmation";
            $description = "₦$request->amount {$item->network->name} Airtime Pin To Cash -- Pin [ $request->airtimeDetails ]";
        }

            $Trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = $item->network->name;
            $ul['service_type'] = "Airtime Cash";
            $ul['description'] = $description;
            $ul['amount'] = $amount_paid;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "2";
            $ul['initial_balance'] = $user->balance;
            $ul['final_balance'] = $user->balance;
            $ul['reference'] = $Trx;
            $ul["message"] = $message;
            $ul['status'] = "pending";
            $tansaction =  $user->transaction()->create($ul);

            if($tansaction){

                if (getSiteSettings("email_notify") == "Yes") {
                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Airtime Cash', $description);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    try {
                        // $this->sendSms($user->phone_number, $description);
                        sendTransactionSms($user, $description, 1);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'successful',
                    'message' => $message,
                    'data' => [
                        'referrence' => $Trx,
                        'service' => "Airtime Cash",
                        'network' => $item->network->name,
                        'amount' => $amount_paid,
                        'date' => $date,
                        'reference' => $Trx,
                        'wallet_balance' => $user->balance,
                    ],
                ]);

            }else{
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
