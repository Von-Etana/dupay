<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Fintech\VirtualCard;
use Illuminate\Http\Request;
use App\TraitsFolder\MailTrait;
use Carbon\Carbon;
use FirebaseUserUpdate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use iSmartFlutterWave;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Illuminate\Support\Str;


class VirtualCardController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    public function index(){
        $user = Auth::user();
        if(count($user->virtualCard) < 1){
            return response(['success' => true, 'message' => "No Virtual Card found", 'data' => $user->virtualCard]);
        }

        foreach ($user->virtualCard as  $card){
           $res = (new iSmartFlutterWave)->getVirtualCardDetail($card->card_id);
           if($res["success"]){
            $card->update([
                "amount" => $res["data"]->amount,
                "masked_pan" => $res["data"]->masked_pan,
                "name_on_card" => $res["data"]->name_on_card,
                "card_pan" => $res["data"]->card_pan,
                "expiration" => $res["data"]->expiration,
                "currency" => $res["data"]->currency,
                "card_type" => $res["data"]->card_type,
                "is_active" => $res["data"]->is_active == true || $res["data"]->is_active == 1 ? true : false,
            ]);
           }
        }
        (new FirebaseUserUpdate)->updateUser($user);
        return response(['success' => true, 'data' => $user->virtualCard]);
    }


    public function getVirtualCardTransaction($id){
         $user = Auth::user();
         $cardTrans = (new iSmartFlutterWave)->getVirtualCardTransaction($id);
         if(!$cardTrans["success"]){
            return response(['success' => false, 'message' => "No transaction found"]);
         }

         (new FirebaseUserUpdate)->updateUser($user);
        return response(['success' => true, 'data' => $cardTrans["data"]]);
    }


    public function store(Request $request){

        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'currency' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();

        //  //check transaction pin

        $cardType = $request->currency;

        //check if user is verify
        if (!$user->hasVerifyWallet) {
            return response(['success' => false, 'message' => "Kindly verify your account"]);
        }

        if (getSiteSettings("enable_virtual_card_creation") != "Yes") {
            return response(['success' => false, 'message' => "Sorry virtual card creation is disabled at the moment try again later"]);
        }

        $checkIfExist = VirtualCard::where('user_id', $user->id)->where("currency",$cardType)->first();

        if($checkIfExist){
            return response(['success' => false, 'message' => "You already have a $cardType card"]);
        }

        if($cardType != "NGN" && $cardType != 'USD'){
            return response(['success' => false, 'message' => "Unknown card request"]);
        }

        if($cardType == "NGN"){
            $cardCharge = getSiteSettings("naira_card_charge");
            $cardAmount = getSiteSettings("naira_card_amount");
            $debitCurrency = getSiteSettings("naira_card_debit_currency");
            $amount = $cardAmount +  $cardCharge;
        }else{
            $cardCharge = getSiteSettings("dollar_card_charge");
            $cardAmount = getSiteSettings("dollar_card_amount");
            $debitCurrency = getSiteSettings("dollar_card_debit_currency");
            $amount = $cardCharge;
        }

        $select_wallet_id = "1";
        $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }

        $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
        $init_balance = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];
        (new FirebaseUserUpdate)->updateUser($user);

        if($balanceAction["success"] != true){
            return response(['success' => false, 'message' => "Unable to access wallet balance"]);
        }

                    $description = "$request->currency Virtual Card creation";
                    $trx = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Virtual Card";
                    $ul['description'] = $description;
                    $ul['amount'] = $amount;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "0";
                    $ul['initial_balance'] = $init_balance;
                    $ul['final_balance'] = $final_balance;
                    $ul['reference'] = $trx;
                    $ul['status'] = "success";
                    $tansaction =  $user->transaction()->create($ul);


       $res =  (new iSmartFlutterWave)->createVirtualCard($user, $cardAmount, $cardType,$debitCurrency);

       if($res["success"] != true){

        //if auto refund card
        if (getSiteSettings("enable_virtual_card_creation_refund") != "Yes") {
                    $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
                    $init_balance = $balanceAction["init_balance"];
                    $final_balance = $balanceAction["final_balance"];
                    $description = "$request->currency Virtual Card creation Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Virtual Card";
                    $ul['description'] = $description;
                    $ul['amount'] = $amount;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $init_balance;
                    $ul['final_balance'] = $final_balance;
                    $ul['reference'] = $trx;
                    $ul['status'] = "refund";
                    $tansaction =  $user->transaction()->create($ul);
        }
        (new FirebaseUserUpdate)->updateUser($user);
        return response(['success' => false, 'message' => "Unable to create card try again later", $res]);
       }



       return response(['success' => true, "message" => "Card Created Successful",  'data' => $user->virtualCard]);
    }



    //fund card
    public function fundVirtualCard(Request $request){


            $date = Carbon::now();
            $data = $request->all();
            $validatedData  = Validator::make($data, [
                'currency' => 'required',
                'amount' => 'required',
                'card_id' => 'required',
            ]);

            if ($validatedData->fails()) {
                $error = "";
                foreach ($validatedData->errors()->all() as $key => $value) {
                    $error .= $value;
                }
                return response(['success' => false, 'message' => $error]);
            }

            $user = Auth::user();


            $cardType = $request->currency;
            $cardId = $request->card_id;

            //check if user is verify
            if (!$user->hasVerifyWallet) {
                return response(['success' => false, 'message' => "Kindly verify your account"]);
            }



            $card = $user->virtualCard()->where('card_id',$cardId)->first();

            if(!$card){
                return response(['success' => false, 'message' => "card not found"]);
            }

            if($cardType != "NGN" && $cardType != 'USD'){
                return response(['success' => false, 'message' => "Unknown card request"]);
            }

            $requestAmount = str_replace(',', '',$request->amount);

            if($cardType == "NGN"){
                if (getSiteSettings("enable_virtual_naira_card") != "Yes") {
                    return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
                }


                $debitCurrency = getSiteSettings("naira_card_debit_currency");
                $fundingAmount = $requestAmount;
                $charge = getSiteSettings("naira_virtual_card_funding_charge");
                $amount = $requestAmount+$charge;
            }else{
                if (getSiteSettings("enable_virtual_dollar_card") != "Yes") {
                    return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
                }
                $buyingRate = getSiteSettings("buying_dollar_rate");
                $debitCurrency = getSiteSettings("dollar_card_debit_currency");
                $fundingAmount = number_format(floatval($requestAmount/$buyingRate),2);
                if ($fundingAmount < floatval(2)) {
                    return response(['success' => false, 'message' => " Minimum funding of 2USD "]);
                }
                $charge = 0;
                $amount = $requestAmount;
            }

            $select_wallet_id = "1";
            $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
            if (!$checkWallet) {
                return response(['success' => false, 'message' => "Insufficient Funds"]);
            }

            $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
            $init_balance = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
            (new FirebaseUserUpdate)->updateUser($user);

            if($balanceAction["success"] != true){
                return response(['success' => false, 'message' => "Unable to access wallet balance"]);
            }

                        $description = "$fundingAmount $request->currency Virtual Card Funding";
                        $trx = strtoupper(Str::random(16));
                        $ul['user_level_id'] = $user->user_level_id;
                        $ul['wallet_id'] = $select_wallet_id;
                        $ul['service'] = "System";
                        $ul['service_type'] = "Virtual Card";
                        $ul['description'] = $description;
                        $ul['amount'] = $requestAmount;
                        $ul['charge'] = $charge;
                        $ul['phone_number'] = $user->phone_number;
                        $ul['type'] = "0";
                        $ul['initial_balance'] = $init_balance;
                        $ul['final_balance'] = $final_balance;
                        $ul['reference'] = $trx;
                        $ul['status'] = "pending";
                        $Fundingtansaction =  $user->transaction()->create($ul);



                        $fundRes =  (new iSmartFlutterWave)->fundVirtualCard($cardId,$fundingAmount,$debitCurrency);

                        if($fundRes["success"] != true){

                         //if auto refund card
                         if (getSiteSettings("enable_virtual_card_funding_refund") == "Yes") {
                                     $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
                                     $init_balance = $balanceAction["init_balance"];
                                     $final_balance = $balanceAction["final_balance"];
                                     $user->transaction()->where(['id' => $Fundingtansaction->id])->update(['status' => 'unsuccessful']);
                                     $description = "$fundingAmount $request->currency Virtual Card Funding Refund";
                                     $trx = strtoupper(Str::random(16));
                                     $ul['user_level_id'] = $user->user_level_id;
                                     $ul['wallet_id'] = $select_wallet_id;
                                     $ul['service'] = "System";
                                     $ul['service_type'] = "Virtual Card";
                                     $ul['description'] = $description;
                                     $ul['amount'] = $amount;
                                     $ul['phone_number'] = $user->phone_number;
                                     $ul['type'] = "1";
                                     $ul['initial_balance'] = $init_balance;
                                     $ul['final_balance'] = $final_balance;
                                     $ul['reference'] = $trx;
                                     $ul['status'] = "refund";
                                     $tansaction =  $user->transaction()->create($ul);
                                     (new FirebaseUserUpdate)->updateUser($user);
                                     (new FirebaseUserUpdate)->addUserNotification($user,
                                     [
                                         "message" => $description,
                                         "type" => "transaction",
                                         "read" => 0,
                                         "data" => [
                                             "service" => 'Virtual Card Funding',
                                             "id" => $tansaction->id,
                                             "reference_id" => $trx,
                                             "status" => "refund",
                                             'date' => $date
                                         ]
                                     ]
                                     );
                                     if (getSiteSettings("email_notify") == "Yes") {
                                        try {
                                            $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Funding', $description);
                                        } catch (\Throwable $th) {
                                            //throw $th;
                                        }
                                    }
                         }

                         if($fundRes["message"] == "Customer does not have sufficient funds in their Rave Available balance"){
                            return response(['success' => false, 'message' => "Unable to fund card try again later"]);
                         }

                         return response(['success' => false, 'message' => $fundRes["message"]]);
                        }


                        //update card details
                        $res = (new iSmartFlutterWave)->getVirtualCardDetail($cardId);
                        if($res["success"]){
                         $card->update([
                             "amount" => $res["data"]->amount,
                             "masked_pan" => $res["data"]->masked_pan,
                             "name_on_card" => $res["data"]->name_on_card,
                             "card_pan" => $res["data"]->card_pan,
                             "expiration" => $res["data"]->expiration,
                             "currency" => $res["data"]->currency,
                             "card_type" => $res["data"]->card_type,
                             "is_active" => $res["data"]->is_active == true || $res["data"]->is_active == 1 ? true : false,
                         ]);
                        }

                        $user->transaction()->where(['id' => $Fundingtansaction->id])->update(['status' => 'success']);

                        (new FirebaseUserUpdate)->addUserNotification($user,
                        [
                            "message" => $description,
                            "type" => "transaction",
                            "read" => 0,
                            "data" => [
                                "service" => 'Virtual Card Funding',
                                "id" => $Fundingtansaction->id,
                                "reference_id" => $trx,
                                "status" => "success",
                                'date' => $date
                            ]
                        ]
                        );

                        if (getSiteSettings("email_notify") == "Yes") {
                            try {
                                $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Funding', $description);
                            } catch (\Throwable $th) {
                                //throw $th;
                            }
                        }

                        (new FirebaseUserUpdate)->updateUser($user);
                        return response()->json([
                            'success' => true,
                            'code' => 200,
                            'status' => 'successful',
                            "message" => "Card Funded Successfully",
                            'data' => [
                                'service' => "Virtual Card Funding",
                                'amount' => $request->amount,
                                'charge' => $charge,
                                'phone_number' => $request->phone_number,
                                'date' => $date,
                                'message' => $description,
                                'reference' => $trx,
                                'wallet_balance' => $final_balance,
                            ],
                            'card' =>  $user->virtualCard,
                            'user' => $user
                        ]);


    }


    public function withdrawFromVirtualCard(Request $request){

        $date = Carbon::now();
            $data = $request->all();
            $validatedData  = Validator::make($data, [
                'currency' => 'required',
                'amount' => 'required',
                'card_id' => 'required',
            ]);

            if ($validatedData->fails()) {
                $error = "";
                foreach ($validatedData->errors()->all() as $key => $value) {
                    $error .= $value;
                }
                return response(['success' => false, 'message' => $error]);
            }

            $user = Auth::user();


            $cardType = $request->currency;
            $cardId = $request->card_id;

            //check if user is verify
            if (!$user->hasVerifyWallet) {
                return response(['success' => false, 'message' => "Kindly verify your account"]);
            }

            $card = $user->virtualCard()->where('card_id',$cardId)->first();

            if(!$card){
                return response(['success' => false, 'message' => "card not found"]);
            }

            if($cardType != "NGN" && $cardType != 'USD'){
                return response(['success' => false, 'message' => "Unknown card request"]);
            }

            $requestAmount = str_replace(',', '',$request->amount);

            if($cardType == "NGN"){
                if (getSiteSettings("enable_virtual_naira_card") != "Yes") {
                    return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
                }
                $debitCurrency = getSiteSettings("naira_card_debit_currency");
                $withdrawAmount = $requestAmount;
                $amount = $requestAmount;
            }else{
                if (getSiteSettings("enable_virtual_dollar_card") != "Yes") {
                    return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
                }
                $sellingRate = getSiteSettings("selling_dollar_rate");
                $debitCurrency = getSiteSettings("dollar_card_debit_currency");
                $withdrawAmount = $requestAmount;
                $amount = number_format(floatval($requestAmount * $sellingRate),2);
            }

            // $cardRes = (new iSmartFlutterWave)->getVirtualCardDetail($cardId);
            // if(!$cardRes["success"]){
            //     return response(['success' => false, 'message' => "Card not found"]);
            // }

            // if($cardRes["data"]->amount < $requestAmount){
            //     return response(['success' => false, 'message' => "Insufficient fund"]);
            // }


            // if($cardType == "NGN" &&  $cardRes["data"]->amount <= 100){
            //     return response(['success' => false, 'message' => "Your card cant be empty"]);
            // }

            // if($cardType == "USD" &&  $cardRes["data"]->amount <= 1){
            //     return response(['success' => false, 'message' => "Your card cant be empty"]);
            // }

            $withdrawRes =  (new iSmartFlutterWave)->withFundFromVirtualCard($cardId,$withdrawAmount);

            //update card details
           $res = (new iSmartFlutterWave)->getVirtualCardDetail($cardId);
           if($res["success"]){
            $card->update([
                "amount" => $res["data"]->amount,
                "masked_pan" => $res["data"]->masked_pan,
                "name_on_card" => $res["data"]->name_on_card,
                "card_pan" => $res["data"]->card_pan,
                "expiration" => $res["data"]->expiration,
                "currency" => $res["data"]->currency,
                "card_type" => $res["data"]->card_type,
                "is_active" => $res["data"]->is_active == true || $res["data"]->is_active == 1 ? true : false,
            ]);
           }

            if(!$withdrawRes["success"]){
                return response(['success' => false, 'message' => $withdrawRes["message"],  'card' =>  $user->virtualCard,
                'user' => $user]);
            }


            $select_wallet_id = "1";
            $balanceAction =  userBalanceAction($user,str_replace(',', '',$amount), $select_wallet_id,"+");
            $init_balance = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
            $description = "Withdrawal of $withdrawAmount $request->currency from your Virtual Card into your wallet";
            $trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $user->user_level_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Virtual Card";
            $ul['description'] = $description;
            $ul['amount'] = $amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $trx;
            $ul['status'] = "success";
            $tansaction =  $user->transaction()->create($ul);
            (new FirebaseUserUpdate)->updateUser($user);
            (new FirebaseUserUpdate)->addUserNotification($user,
            [
                "message" => $description,
                "type" => "transaction",
                "read" => 0,
                "data" => [
                    "service" => 'Virtual Card Withdrawal',
                    "id" => $tansaction->id,
                    "reference_id" => $trx,
                    "status" => "success",
                    'date' => $date
                ]
            ]
            );
            if (getSiteSettings("email_notify") == "Yes") {
               try {
                   $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Withdrawal', $description);
               } catch (\Throwable $th) {
                   //throw $th;
               }
           }




           return response()->json([
            'success' => true,
            'code' => 200,
            'status' => 'successful',
            "message" => "Card Fund Withdrawal Successful",
            'data' => [
                'service' => "Virtual Card Withdrawal",
                'amount' => $request->amount,
                'charge' => 0,
                'phone_number' => $request->phone_number,
                'date' => $date,
                'message' => $description,
                'reference' => $trx,
                'wallet_balance' => $final_balance,
            ],
            'card' =>  $user->virtualCard,
            'user' => $user
        ]);



    }


    public function virtualCardStatusUpdate(Request $request){

            $date = Carbon::now();
            $data = $request->all();
                $validatedData  = Validator::make($data, [

                'card_id' => 'required',
            ]);

            if ($validatedData->fails()) {
                $error = "";
                foreach ($validatedData->errors()->all() as $key => $value) {
                    $error .= $value;
                }
                return response(['success' => false, 'message' => $error]);
            }

            $user = Auth::user();
            $cardType = $request->currency;
            $cardId = $request->card_id;

            $card = $user->virtualCard()->where('card_id',$cardId)->first();

            if(!$card){
                return response(['success' => false, 'message' => "card not found"]);
            }

            $cardRes = (new iSmartFlutterWave)->getVirtualCardDetail($cardId);
            if(!$cardRes["success"]){
                return response(['success' => false, 'message' => "Card not found"]);
            }

            $block =   $cardRes["data"]->is_active == true || $cardRes["data"]->is_active == 1 ? "block" : "unblock";
            $cardUpdateRes = (new iSmartFlutterWave)->updateVirtualCardStatus($cardId, $block);


             //update card details
           $res = (new iSmartFlutterWave)->getVirtualCardDetail($cardId);
           if($res["success"]){
            $card->update([
                "amount" => $res["data"]->amount,
                "masked_pan" => $res["data"]->masked_pan,
                "name_on_card" => $res["data"]->name_on_card,
                "card_pan" => $res["data"]->card_pan,
                "expiration" => $res["data"]->expiration,
                "currency" => $res["data"]->currency,
                "card_type" => $res["data"]->card_type,
                "is_active" =>  $res["data"]->is_active == true || $res["data"]->is_active == 1 ? true : false,
            ]);
           }

            return response(['success' => $cardUpdateRes["success"], 'message' => $cardUpdateRes["message"], 'card' =>  $user->virtualCard,
            'user' => $user]);

        }


}
