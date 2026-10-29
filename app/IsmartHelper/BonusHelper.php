<?php

use App\Models\PaymentGateway;
use App\Models\Profile;
use App\Models\Telecom\VirtualAccountType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;

/************************************************
            by Ibrahim Yekeen Olamikan
            iSmart Olami Technology
**************************************************/

function referralCommission($user, $transaction, $amount)
{
    if ($user->referral != null && getSiteSettings("enable_purchase_commission") == "Yes"){
        $referral =  Profile::whereRefId($user->referral)->orWhere("username",$user->referral)->first();
        if($referral){
            $refUser = $referral->user;

            // GUARD: the profile may be orphaned (its user record was deleted),
            // so $referral->user can be null. Calling referred_users() on null
            // throws a fatal error and 500s the whole transfer. If there's no
            // referrer user, there's simply no commission to pay — exit cleanly.
            if (!$refUser) {
                return;
            }

            $referred_users_count = $refUser->referred_users()->count();

            $bonus = getSiteSettings("purchase_commission_percentage");
            $bonus = $bonus / 100 * (float)$amount;

            //check if commission is by certain user
            if(getSiteSettings("enable_referral_min_user") == "Yes"){
               $referralMinUser =  getSiteSettings("referral_min_user");
               if($referralMinUser > $referred_users_count){

                   if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                    $text = "Oops!!! you just lost ₦$bonus commission of ".$user->profile->username." $transaction->service_type purchase of ".getPrice($amount)." because your total refer doesnt meet up with our purchase commission requirement of $referralMinUser referral from you, refer more friends & family to earn commission from all their purchases";
                    try {
                        MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                return;
               }
            }



            // if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "app") {
            //     if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
            //         $text = "Oops!!! you just lost ₦$bonus commission of ".$user->profile->username." $transaction->service_type purchase of ".getPrice($amount)." <br>to get commission encourage your referral to make use of our official mobile application";
            //         try {
            //             MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
            //         } catch (\Throwable $th) {
            //             //throw $th;
            //         }
            //     }
            //     return;
            // }

            $trx = strtoupper(Str::random(20));
            $ul['wallet_id'] = "3";
            $ul['user_level_id'] = $refUser->user_level_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Purchase Commission";
            $ul['description'] = "₦$bonus commission from ".$user->profile->username." on ".getPrice($amount)." ".$transaction->service_type." purchase";
            $ul['amount'] = $bonus;
            $ul['phone_number'] = $refUser->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $refUser->profile->commission ?? 0.0;
            $ul['final_balance'] = $refUser->profile->commission ?? 0.0 + $bonus;
            $ul['reference'] = $trx;
            $ul['status'] = "success";
            $transaction =  $refUser->transaction()->create($ul);

            if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                $text = "Congratulations!!! you just receive ₦$bonus commission from ".$user->profile->username." $transaction->service_type purchase of ".getPrice($amount)."  Reference ID Is : #$trx";

                (new FirebaseUserUpdate)->addUserNotification($refUser,
                [
                    "message" => $text,
                    "type" => "transaction",
                    "read" => 0,
                    "data" => [
                        "service" => 'Purchase Commission',
                        "id" => $transaction->id,
                        "reference_id" => $transaction->reference,
                        "status" => $transaction->status,
                        'date' => $transaction->created_at
                    ]
                ]
                );

                try {
                    MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            if (getSiteSettings("phone_notify") == "Yes" && $refUser->phone_number != null){
                $text = "₦$bonus Commission For Referring ".$user->profile->username."  Reference ID Is : #$trx";
                try {
                    MailTrait::sendSms($refUser->phone_number,$text);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            
            // $refUser->balance = $refUser->balance + $bonus;
            // $refUser->save();
            
            // update the user commission balance
            $refUser->profile->commission = $refUser->profile->commission + $bonus;
            $refUser->profile->save();

        }
    }
}
    
class iSmartBonus
{


    static function referralCommission($user, $transaction){

        if ($user->referral != null && getSiteSettings("enable_purchase_commission") == "Yes"){
            $referral =  Profile::whereRefId($user->referral)->orWhere("username",$user->referral)->first();
            if($referral){
                $refUser = $referral->user;
                $referred_users_count = $refUser->referred_users()->count();

                $bonus = getSiteSettings("purchase_commission_percentage");
                $bonus = $bonus/100 * $transaction->amount;

                //check if commission is by certain user
                if(getSiteSettings("enable_referral_min_user") == "Yes"){
                   $referralMinUser =  getSiteSettings("referral_min_user");
                   if($referralMinUser > $referred_users_count){

                       if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                        $text = "Oops!!! you just lost ₦$bonus commission of ".$user->profile->username." $transaction->service_type purchase of ".getPrice($transaction->amount)." because your total refer doesnt meet up with our purchase commission requirement of $referralMinUser referral from you, refer more friends & family to earn commission from all their purchases";
                        try {
                            MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }

                    return;
                   }
                }



                if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "app") {
                    if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                        $text = "Oops!!! you just lost ₦$bonus commission of ".$user->profile->username." $transaction->service_type purchase of ".getPrice($transaction->amount)." <br>to get commission encourage your referral to make use of our official mobile application";
                        try {
                            MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }
                    return;
                }

                $trx = strtoupper(Str::random(20));
                $ul['wallet_id'] = "3";
                $ul['user_level_id'] = $refUser->user_level_id;
                $ul['service'] = "System";
                $ul['service_type'] = "Purchase Commission";
                $ul['description'] = "₦$bonus commission from ".$user->profile->username." on ".getPrice($transaction->amount)." ".$transaction->service_type;
                $ul['amount'] = $bonus;
                $ul['phone_number'] = $refUser->phone_number;
                $ul['type'] = "1";
                $ul['initial_balance'] = $refUser->profile->commission ?? 0.0;
                $ul['final_balance'] = $refUser->profile->commission ?? 0.0 + $bonus;
                $ul['reference'] = $trx;
                $ul['status'] = "success";
                $transaction =  $refUser->transaction()->create($ul);

                if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                    $text = "Congratulations!!! you just receive ₦$bonus commission from ".$user->profile->username." $transaction->service_type purchase of ".getPrice($transaction->amount)."  Reference ID Is : #$trx";

                    (new FirebaseUserUpdate)->addUserNotification($refUser,
                    [
                        "message" => $text,
                        "type" => "transaction",
                        "read" => 0,
                        "data" => [
                            "service" => 'Purchase Commission',
                            "id" => $transaction->id,
                            "reference_id" => $transaction->reference,
                            "status" => $transaction->status,
                            'date' => $transaction->created_at
                        ]
                    ]
                    );

                    try {
                        MailTrait::sendMail($refUser->email,$refUser->profile->username,'Purchase Commission',$text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes" && $refUser->phone_number != null){
                    $text = "₦$bonus Commission For Referring ".$user->profile->username."  Reference ID Is : #$trx";
                    try {
                        MailTrait::sendSms($refUser->phone_number,$text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                //update the user commission balance
                $refUser->profile->commission = $refUser->profile->commission + $bonus;
                $refUser->profile->save();

            }
        }
    }


   static function addReferralCommission($user, $amount){
        if ($user->referral != null && getSiteSettings("enable_referral_bonus") == "Yes"){
                    //check referral
                   $refarral =  Profile::whereRefId($user->referral)->orWhere("username",$user->referral)->first();
                   if($refarral){
                    $refUser = User::where("id",$refarral->user_id)->first();
                    if($refUser){
                    $bonus = getSiteSettings("referral_bonus");
                    $bonus = $bonus/100 * $amount;
                    $trx = strtoupper(Str::random(20));
                    $ul['wallet_id'] = "3";
                    $ul['user_level_id'] = $refUser->user_level_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Referral Commission";
                    $ul['description'] = "₦$bonus Commission For Referring ".$user->profile->username;
                    $ul['amount'] = $bonus;
                    $ul['phone_number'] = $refUser->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $refUser->profile->commission ?? 0.0;
                    $ul['final_balance'] = $refUser->profile->commission ?? 0.0 + $bonus;
                    $ul['reference'] = $trx;
                    $ul['status'] = "success";
                    $transaction = $refUser->transaction()->create($ul);

                    if (getSiteSettings("email_notify") == "Yes" && $refUser->email !=  null ){
                        $text = "Congratulations!!! you just recieve ₦$bonus Commission For Referring ".$user->profile->username." from his/her first deposit of $amount  Reference ID Is : #$trx";


                        try {
                            (new FirebaseUserUpdate)->addUserNotification($refUser,
                            [
                                "message" => $text,
                                "type" => "transaction",
                                "read" => 0,
                                "data" => [
                                    "service" => 'Referral Commission',
                                    "id" => $transaction->id,
                                    "reference_id" => $transaction->reference,
                                    "status" => $transaction->status,
                                    'date' => $transaction->created_at
                                ]
                            ]
                            );
                            MailTrait::sendMail($refUser->email,$refUser->profile->username,'Referral Commission',$text);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }
                    if (getSiteSettings("phone_notify") == "Yes" && $refUser->phone_number != null){
                        $text = "₦$bonus Commission For Referring ".$user->profile->username."  Reference ID Is : #$trx";
                        try {
                            MailTrait::sendSms($refUser->phone_number,$text);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }

                    //update the user commission balance
                    $refUser->profile->commission = $refUser->profile->commission + $bonus;
                    $refUser->profile->save();
                }

                }
            }
     }

  static  function addUpBonus($user, $bonus, $transactionReference, $description ){
        //check bonus on 1st deposit
            $bonusBalance = $user->bonus;
            $user->bonus = $user->bonus + $bonus;
            $user->save();

            //inset bonus transaction
            $trx = strtoupper(Str::random(20));
            $ul['wallet_id'] = "2";
            $ul['service'] = "System";
            $ul['service_type'] = "Funding Bonus";
            $ul['description'] = $description;
            $ul['amount'] = $bonus;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $bonusBalance;
            $ul['final_balance'] = $bonusBalance + $bonus;
            $ul['reference'] = $trx;
            $ul['paymentReference'] = $transactionReference;
            $ul['status'] = "success";
            $transaction =  $user->transaction()->create($ul);

            try {
                (new FirebaseUserUpdate)->addUserNotification($user,
                [
                    "message" => $description,
                    "type" => "transaction",
                    "read" => 0,
                    "data" => [
                        "service" => 'Funding Bonus',
                        "id" => $transaction->id,
                        "reference_id" => $transaction->reference,
                        "status" => $transaction->status,
                        'date' => $transaction->created_at
                    ]
                ]
                );
                if (getSiteSettings("email_notify") == "Yes") {
                    $text = $description . " <br> Reference ID Is : #$trx";
                    MailTrait::sendMail($user->email, $user->profile->username, 'Wallet Funding Bonus', $text);
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = $description . " <br> Reference ID Is : #$trx";
                    MailTrait::sendSms($user->phone_number, $text);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

        }


    //airtime bonus on register
    static function airtimeBonus($user){
        if(getSiteSettings("enable_airtime_bonus") != "Yes"){
            return [
                "success" => false,
                "message" => "Airtime Bonus is Disabled try again later",
            ];
        }

        if(getSiteSettings("enable_airtime_bonus_must_fund") == "Yes"){
            if(!$user->hasFundWallet){
                return [
                    "success" => false,
                    "message" => "Please Kindly fund your wallet to activate your bonus account",
                ];
            }

            if(getSiteSettings("airtime_bonus_fund") > $user->payment()->sum('amount')){
                return [
                    "success" => false,
                    "message" => "Not Qualify due to wallet funding",
                ];
            }
        }


        return [
            "success" => true,
            "amount" => getSiteSettings("airtime_bonus_amount"),
        ];

    }




}