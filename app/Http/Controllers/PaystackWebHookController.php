<?php

namespace App\Http\Controllers;

use App\Models\Fintech\WithdrawalLog;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use FirebaseUserUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use iSmartBonus;

class PaystackWebHookController extends Controller
{

    public function __construct()

    {
        //$this->verifHash = "ismartTechVerifyAsh9393nnhfeotdeket";
        $this->gateWay = PaymentGateway::where("name", "like", "%Paystack%")->FirstOrFail();
        $this->apiPayLoad = json_decode($this->gateWay->value_key, true);
        if (!$this->gateWay || $this->apiPayLoad == null ||  !is_array($this->apiPayLoad)) {
            return [
                "success" => false,
                "message" => "Payment Value Key not valid contact developer"
            ];
        }
        $this->secret_key = $this->apiPayLoad["secret_key"];
    }


    public function index(Request $request)
    {


        $json = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        if (!$request->isMethod('post') || !$signature || !$this->secret_key || !hash_equals(hash_hmac('sha512', $json, $this->secret_key), $signature)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }
        $response = json_decode($json);
        if (!is_object($response)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }
        $this->webhookResponse = $response;

        $requestMethod = $response->event;

        //check if its bank transfer callback
        if ($requestMethod == "transfer.success") {
            //$this->bankTransfer();
        } else if ($requestMethod == "transfer.failed") {
            $this->bankTransfer("FAILED");
        } else if ($requestMethod == "transfer.reversed") {
            // $this->virtualAccountTransferHook();
        } else {
            if ($requestMethod == "charge.success") {
                $this->otherPayment();
            }
        }
    }

    public function bankTransfer($status)
    {
        $response = $this->webhookResponse ?? null;

        if (!empty($response) && $response->data->reference != null) {
            $paymentMethod = $response->event;

            $id = $response->data->id;
            $reference = $response->data->reference;

            $transaction = Transaction::where('reference', $reference)->first();
            $withdrawLog = WithdrawalLog::where('referrence', $reference)->first();

            if (!$transaction || !$withdrawLog) {
                return;
            }

            $user = $transaction->user;
            $amount = $transaction->amount;
            $select_wallet_id = $transaction->wallet_id;

            //check if fail
            if ($status == "FAILED") {
                $transaction->update(['status' => "unsuccessful"]);
                $withdrawLog->update(['status' => "4"]);
                $Trx = strtoupper(Str::random(16));
                $date = Carbon::now();

                //check if auto refund bank transfer
                if (getSiteSettings('enable_auto_withdrawal_refund') == "Yes") {
                    $balanceAction =  userBalanceAction($user, $amount, $select_wallet_id, "+");
                    $init_balance = $balanceAction["init_balance"];
                    $final_balance = $balanceAction["final_balance"];
                    if ($balanceAction["success"]) {
                        $description = $transaction->description . " Refund";
                        $trx = strtoupper(Str::random(16));
                        $ul['user_level_id'] = $user->user_level_id;
                        $ul['service'] = "System";
                        $ul['wallet_id'] = $select_wallet_id;
                        $ul['service_type'] = "Fund Withdrawal";
                        $ul['description'] = $description;
                        $ul['amount'] = $amount;
                        $ul['phone_number'] = $transaction->phone_number;
                        $ul['type'] = "1";
                        $ul['initial_balance'] = $init_balance;
                        $ul['final_balance'] = $final_balance;
                        $ul['reference'] = $trx;
                        $ul['reference_ref'] = $Trx;
                        $ul['status'] = "refund";
                        $ul['created_at'] = $date;
                        $user->transaction()->create($ul);
                    }
                }

                // (new FirebaseUserUpdate)->addUserNotification(
                //     $user,
                //     [
                //         "message" => $description,
                //         "type" => "transaction",
                //         "read" => 0,
                //         "data" => [
                //             "service" => 'Wallet Funding',
                //             "id" => $transaction->id,
                //             "reference_id" => $Trx,
                //             "status" => $transaction->status,
                //             'date' => $date
                //         ]
                //     ]
                // );
            }
            //empty the paymentLog file
            //(new FirebaseUserUpdate)->updateUser($user);
        }
    }




    public function otherPayment()
    {
        $response = $this->webhookResponse ?? null;

        if (!empty($response) && $response->event == "charge.success") {
            $id = $response->data->id;
            $amountPaid = $response->data->amount / 100;
            $paymentStatus = $response->data->status;
            $currency = $response->data->currency;
            $reference = $response->data->reference;
            $email = $response->data->customer->email;
            $paidOn = $response->data->paid_at;

            $transaction =    Transaction::where('paymentReference', $reference)->first();
            $withdrawLog = Payment::where('reference', $reference)->first();

            if ($transaction || $withdrawLog) {
                return;
            }
            //verify payment from flutterwave
            //  $verify =    (new iSmartFlutterWave)->verifyTransaction($id,$amountPaid,$currency);
            //     //if success
            //  if($verify["success"]){

            //get user
            $user = User::where("email", $email)->first();
            if (!$user) {
                return response(['success' => false, 'message' => "User Not Found"]);
            }


            $checkIfNotFirstPayment = Payment::where("user_id", $user->id)->first();
            $gateWayData = PaymentGateway::findOrFail("2");

            //insert the payment details to user records
            $trans =   $user->payment()->create([
                "payment_gateway_id" => 2,
                "type" => "Atm Method",
                "reference" => $reference,
                "amount" => $amountPaid,
                "status" => "success",
                "date" => $paidOn
            ]);

            if (!$trans) {
                return response(['success' => false, 'message' => "Unknown error occure try again later"]);
            }


            //calculate charge
            if ($gateWayData->rate == "FLAT") {
                $rate = ($gateWayData->charge / 100) * $amountPaid;
                $amount_to_credit = $amountPaid  - $rate;
            } else {
                $amount_to_credit = $amountPaid  - $gateWayData->charge;
            }
            $balance = $user->balance;
            // RACE-SAFE credit: row-locked, atomic. $user is kept in sync by the helper.
                    $creditAction = userBalanceAction($user, $amount_to_credit, "1", "+");
                    if (empty($creditAction["success"])) {
                        \Log::error("Funding credit failed", ["user_id" => $user->id, "reason" => $creditAction["reason"] ?? null]);
                        return response(["success" => false, "message" => "Could not credit wallet, please contact support."]);
                    }
            //empty the file


            $description = "Payment of ₦$amountPaid into wallet from paystack";
            $Trx = strtoupper(Str::random(20));
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
            $ul['reference'] = $Trx;
            $ul['paymentReference'] = $reference;
            $ul['status'] = "success";
            $transaction =  $user->transaction()->create($ul);


            try {
                // (new FirebaseUserUpdate)->addUserNotification(
                //     $user,
                //     [
                //         "message" => $description,
                //         "type" => "transaction",
                //         "read" => 0,
                //         "data" => [
                //             "service" => 'Wallet Funding',
                //             "id" => $transaction->id,
                //             "reference_id" => $transaction->reference,
                //             "status" => $transaction->status,
                //             'date' => $transaction->created_at
                //         ]
                //     ]
                // );
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
            if (!$checkIfNotFirstPayment) {
                iSmartBonus::addReferralCommission($user, $amountPaid);
            }


            //check bonus on 1st deposit
            if (!$checkIfNotFirstPayment && getSiteSettings("enable_first_deposit_bonus") == "Yes") {
                $bonusPercentage = getSiteSettings("first_deposit_bonus");
                $bonus = $bonusPercentage / 100 * $amountPaid;
                $description = "Bonus of ₦$bonus on your first deposit of ₦$amountPaid";
                //inset bonus transaction
                iSmartBonus::addUpBonus($user, $bonus, $reference, $description);
            }
            //check bonus of every deposit
            elseif (getSiteSettings("enable_deposit_bonus") == "Yes" && getSiteSettings("deposit_bonus") > 0) {
                $bonusPercentage = getSiteSettings("deposit_bonus");
                $bonus = $bonusPercentage / 100 * $amountPaid;
                $description = "Bonus of ₦$bonus on your deposit of ₦$amountPaid";
                //inset bonus transaction
                iSmartBonus::addUpBonus($user, $bonus, $reference, $description);
            }

            // (new FirebaseUserUpdate)->updateUser($user);
            // return response()->json([
            //     'success' => true,
            //     'code' => 200,
            //     'status' => 'successful',
            //     'next_url' => route('UserTransactionDetails', $Trx),
            //     'data' => [
            //         'service' => "Wallet Funding",
            //         'amount' => $amountPaid,
            //         'date' => $paidOn,
            //         'reference' => $Trx,
            //         'wallet_balance' => $user->balance,
            //     ],
            // ]);


            //}

        }
    }

    /*public function atmWebhook(Request $request){

        $date = Carbon::now('Africa/Lagos');
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'user_id' => 'required',
            'amountPaid' => 'required',
            'paymentReference' => 'required',
            'transactionId' => 'required',
        ]);


        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $paymentReference = $request->paymentReference;
	    $amountPaid = $request->amountPaid;
	    $transactionId = $request->transactionId;


        $user = User::where("id", $request->user_id)->first();
        if(!$user){
            return response(['success' => false, 'message' => "User Not Found"]);
        }

        $checkPayment = Payment::where("reference", $paymentReference)->first();

        if($checkPayment){
            return response(['success' => false, 'message' => "Payment Already Exist"]);
        }

        //verify transaction from flutterwave
        $verifyPament = (new iSmartFlutterWave)->verifyTransaction($transactionId, $amountPaid);


        if($verifyPament["success"] != true){
            return response(['success' => false, 'message' => "Invalid Payment"]);
        }

        $checkIfNotFirstPayment = Payment::where("user_id", $user->id)->first();
        $gateWayData = PaymentGateway::findOrFail("2");

         //insert the payment details to user records
         $trans =   $user->payment()->create([
            "payment_gateway_id" => 2,
            "type" => "Atm Method",
            "reference" => $paymentReference,
            "amount" => $amountPaid,
            "status" => "success",
            "date" => $verifyPament["data"]["created_at"]
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
        $ul['paymentReference'] = $paymentReference;
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
        addUpBonus($user, $bonus, $paymentReference, $description );
    }
    //check bonus of every deposit
    elseif(getSiteSettings("enable_deposit_bonus") == "Yes" && getSiteSettings("deposit_bonus") > 0){
        $bonusPercentage = getSiteSettings("deposit_bonus");
        $bonus = $bonusPercentage/100 * $amountPaid;
        $description = "Bonus of ₦$bonus on your deposit of ₦$amountPaid";
        //inset bonus transaction
        addUpBonus($user, $bonus, $paymentReference, $description );
    }

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
