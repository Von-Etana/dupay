<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Telecom\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;
use Commission;
use FirebaseUserUpdate;
use Illuminate\Http\Client\Response;
use iSmartBonus;
use iSmartMonnify;

class WebHookController extends Controller
{

    use MailTrait;


    public function monnifyVerifyTransaction($paymentRef){
        return (new iSmartMonnify)->verifyPaymentDetails($paymentRef);
    }

    public function monnifyWebHook(Request $request){
        $gateWayData = PaymentGateway::where("name", "like", "%Monnify%")->firstOrFail();
        if($gateWayData && $gateWayData->status == "1"){
        $response = json_decode($request->getContent());
        if (!is_object($response) || empty($response->eventData->paymentReference)) {
            return response()->json(['message' => 'Invalid event'], 400);
        }

    //check if the response is not empty
    if (!empty($response) && $response->eventData->paymentReference != null) {
        $paymentRef = $response->eventData->paymentReference;
	    $amountPaid = $response->eventData->amountPaid;

	    $paidOn = $response->eventData->paidOn;
	    $transactionReference = $response->eventData->transactionReference;
	    $email = $response->eventData->customer->email;
        $paymentMethod = $response->eventData->paymentMethod;
        $paymentStatus = $response->eventData->paymentStatus;
        $destination = $response->eventData->destinationAccountInformation->accountNumber;

       if($response->eventData->paymentSourceInformation[0]->accountName != "N/A "){
           $from = "from ".$response->eventData->paymentSourceInformation[0]->accountName;
       }else{
           $from = "";
       }



        $verifyPament = (new iSmartMonnify)->verifyPaymentDetails($paymentRef);

            if($paymentStatus == "PAID" && $verifyPament == "successful"){
            $user = User::where("email", $email)->first();
            $checkPayment = Payment::where("reference", $transactionReference)->first();
            //check if payment does not exist before and user exists
            if(!$checkPayment){
                
                if(!$user){
                    header("HTTP/1.1 400 Bad Request", true, 400);
header("Content-Type: text/plain"); // Set the content type
echo "Error user not found"; 
die();
                }

            $checkIfNotFirstPayment = Payment::where("user_id", $user->id)->first();

                //insert the payment details to user records
              $trans =   $user->payment()->create([
                    "payment_gateway_id" => 1,
                    "type" => "Virtual Account",
                    "reference" => $transactionReference,
                    "amount" => $amountPaid,
                    "status" => "success",
                    "date" => $paidOn
                ]);

                if($trans){
                    //calculate charge
                    if ($gateWayData->rate == "FLAT") {
                        $rate = $gateWayData->charge/100 * $amountPaid;
                        $amount_to_credit = $amountPaid - $rate;
                    }else{
                        $amount_to_credit = $amountPaid - $gateWayData->charge;
                    }
                    $balance = $user->balance;
                    // RACE-SAFE credit: row-locked, atomic. $user is kept in sync by the helper.
                    $creditAction = userBalanceAction($user, $amount_to_credit, "1", "+");
                    if (empty($creditAction["success"])) {
                        \Log::error("Funding credit failed", ["user_id" => $user->id, "reason" => $creditAction["reason"] ?? null]);
                        return response(["success" => false, "message" => "Could not credit wallet, please contact support."]);
                    }


                    $description = "₦$amountPaid credited into your $destination wallet $from";
                    $trx = strtoupper(Str::random(20));
                    $ul['wallet_id'] = "1";
                    $ul['service'] = "System";
                    $ul['service_type'] = "Wallet Funding";
                    $ul['description'] = $description;
                    $ul['amount'] = $amountPaid;
                    $ul['real_price'] = $amount_to_credit;
                    $ul['charge'] = $amountPaid - $amount_to_credit;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $balance;
                    $ul['final_balance'] = $balance + $amount_to_credit;
                    $ul['reference'] = $trx;
                    $ul['paymentReference'] = $transactionReference;
                    $ul['status'] = "success";
                    $tansaction =   $user->transaction()->create($ul);


            try {
                if (getSiteSettings("email_notify") == "Yes") {
                    $text = $description . " <br> Reference ID Is : #$trx";
                    $this->sendMail($user->email, $user->profile->username ?? "", "₦$amountPaid Wallet Deposit", $text);
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = $description . " <br> Reference ID Is : #$trx";
                    $this->sendSms($user->phone_number, $text);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            //refferal commission
    if(!$checkIfNotFirstPayment){
        iSmartBonus::addReferralCommission($user, $amountPaid);
    }

            //check bonus on 1st deposit
        if(!$checkIfNotFirstPayment && getSiteSettings("enable_first_deposit_bonus") == "Yes"){
            $bonusPercentage = getSiteSettings("first_deposit_bonus");
            $bonus = $bonusPercentage/100 * $amountPaid;
            $description = "Bonus of ₦$bonus on your first deposit of ₦$amountPaid";
            //inset bonus transaction
          iSmartBonus::addUpBonus($user, $bonus, $transactionReference, $description );
        }
        //check bonus of every deposit
        elseif(getSiteSettings("enable_deposit_bonus") == "Yes" && getSiteSettings("deposit_bonus") > 0){
            $bonusPercentage = getSiteSettings("deposit_bonus");
            $bonus = $bonusPercentage/100 * $amountPaid;
            $description = "Bonus of ₦$bonus on your deposit of ₦$amountPaid";
            //inset bonus transaction
            iSmartBonus:: addUpBonus($user, $bonus, $transactionReference, $description );
        }


                }
            }else{
                header("HTTP/1.1 400 Bad Request", true, 400);
header("Content-Type: text/plain"); // Set the content type
echo "Error Transaction already exist"; 
die();
            }
            }else{
                header("HTTP/1.1 400 Bad Request", true, 400);
header("Content-Type: text/plain"); // Set the content type
echo "Error invalide payment"; 
die();
            }
        //}
    //}
    }else{
         header("HTTP/1.1 400 Bad Request", true, 400);
header("Content-Type: text/plain"); // Set the content type
echo "Error payment details not found"; 
die();
    }

        }
        (new FirebaseUserUpdate)->updateUser($user);
    }

    /*public function monnifyAtmWebhook(Request $request){

        $date = Carbon::now('Africa/Lagos');
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'user_id' => 'required',
            'amountPaid' => 'required',
            'paidOn' => 'required',
            'paymentReference' => 'required',
            'paymentStatus' => 'required',
            'transactionReference' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $paymentRef = $request->paymentReference;
	    $amountPaid = $request->amountPaid;
	    $paidOn = $request->paidOn;
	    $transactionReference = $request->transactionReference;

        $user = User::where("id", $request->user_id)->first();
        if(!$user){
            return response(['success' => false, 'message' => "User Not Found"]);
        }


        $checkPayment = Payment::where("reference", $request->transactionReference)->first();

        if($checkPayment){
            return response(['success' => false, 'message' => "Payment Already Exist"]);
        }

        if(verifyMonnifyPaymentDetails($paymentRef) != "successful"){
            return response(['success' => false, 'message' => "Invalid Payment"]);
        }


        $checkIfNotFirstPayment = Payment::where("user_id", $user->id)->first();
        $gateWayData = PaymentGateway::findOrFail("1");

        //insert the payment details to user records
        $trans =   $user->payment()->create([
            "payment_gateway_id" => 1,
            "type" => "Atm Method",
            "reference" => $transactionReference,
            "amount" => $amountPaid,
            "status" => "success",
            "date" => $paidOn
        ]);

        if(!$trans){
            return response(['success' => false, 'message' => "Unknown error occure try again later"]);
        }
            //calculate charge
            if ($gateWayData->rate == "FLAT") {
                $rate = $gateWayData->charge/100 * $amountPaid;
                $amount_to_credit = $amountPaid - $rate;
            }else{
                $amount_to_credit = $amountPaid - $gateWayData->charge;
            }
            $balance = $user->balance;
            // RACE-SAFE credit: row-locked, atomic. $user is kept in sync by the helper.
                    $creditAction = userBalanceAction($user, $amount_to_credit, "1", "+");
                    if (empty($creditAction["success"])) {
                        \Log::error("Funding credit failed", ["user_id" => $user->id, "reason" => $creditAction["reason"] ?? null]);
                        return response(["success" => false, "message" => "Could not credit wallet, please contact support."]);
                    }

            $description = "Payment of ₦$amountPaid into wallet";
            $Trx = strtoupper(Str::random(20));
            $ul['service'] = "System";
            $ul['service_type'] = "Wallet Funding";
            $ul['description'] = $description;
            $ul['amount'] = $amountPaid;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $balance;
            $ul['final_balance'] = $balance + $amount_to_credit;
            $ul['reference'] = $Trx;
            $ul['paymentReference'] = $transactionReference;
            $ul['status'] = "success";
            $user->transaction()->create($ul);
    try {
        if (getSiteSettings("email_notify") == "Yes") {
            $text = $description . "₦$amountPaid funded into your wallet Reference ID Is : #$Trx";
            $this->sendMail($user->email, $user->profile->username, "₦$amountPaid Account Deposit", $text);
        }
        if (getSiteSettings("phone_notify") == "Yes") {
            $text = $description . "₦$amountPaid funded into your wallet Reference ID Is : #$Trx";
            $this->sendSms($user->phone, $text);
        }
    } catch (\Throwable $th) {
        //throw $th;
    }

    //refferal commission
    if(!$checkIfNotFirstPayment){
        addReferralCommission($user, $amountPaid);
    }

        //check bonus on 1st deposit
        if(!$checkIfNotFirstPayment && getSiteSettings("enable_first_deposit_bonus") == "Yes"){
            $bonusPercentage = getSiteSettings("first_deposit_bonus");
            $bonus = $bonusPercentage/100 * $amountPaid;
            $description = "Bonus of ₦$bonus on your first deposit of ₦$amountPaid";
            //inset bonus transaction
            addUpBonus($user, $bonus, $transactionReference, $description );
        }
        //check bonus of every deposit
        elseif(getSiteSettings("enable_deposit_bonus") == "Yes" && getSiteSettings("deposit_bonus") > 0){
            $bonusPercentage = getSiteSettings("deposit_bonus");
            $bonus = $bonusPercentage/100 * $amountPaid;
            $description = "Bonus of ₦$bonus on your deposit of ₦$amountPaid";
            //inset bonus transaction
            addUpBonus($user, $bonus, $transactionReference, $description );
        }

        (new FirebaseUserUpdate)->updateUser($user);

    return response()->json([
        'success' => true,
        'code' => 200,
        'status' => 'successful',
        'next_url' => route('UserTransactionDetails', $Trx),
        'data' => [
            'service' => "Wallet Funding",
            'amount' => $amountPaid,
            'date' => $date,
            'reference' => $Trx,
            'wallet_balance' => $user->balance,
        ],
    ]);


    }*/

}
