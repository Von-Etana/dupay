<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\Exam;
use App\Models\Telecom\ExamPin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\TraitsFolder\MailTrait;

class ExamController extends Controller
{
    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser');
        $this->middleware('auth:api');
    }

    public function calAmount(Request $request){
        $data = $request->all();
        $validatedData  = Validator ::make($data, [
            'exam' => 'required',
            'quantity' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();
        $item = Exam::find($request->exam);

        if (!$item) {
            return response(['success' => false, 'message' => "Exam Does not Exist"]);
        }

        if ($item->status == "0" ) {
            return response(['success' => false, 'message' => "Exam Is Currently not Available try again later"]);
        }

        $price = $request->quantity * $item->amount;
        return json_encode($price);
    }


    public function buyExam(Request $request){
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator ::make($data, [
            'exam_id' => 'required',
            'quantity' => 'required',
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

        //check selected wallet
        $select_wallet_id = $request->wallet_id ?? "1";
        if (!checkWalletEnable("Exam", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }


        $item = Exam::find($request->exam_id);
        $Trx = strtoupper(Str::random(16));

        if (!$item) {
            return response(['success' => false, 'message' => "Exam Does not Exist"]);
        }

        if ($item->status == "0" ) {
            return response(['success' => false, 'message' => "Exam Is Currently not Available try again later"]);
        }

        // $userTransaction = $user->transaction()->latest()->first();
        // if($userTransaction){
        //     if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  $item->name)
        //     {
        //         //$tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
        //         return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

        //     }
        // }

        $price = $request->quantity * $item->amount;
        $quantity = $request->quantity;
        $exam = $item->name;

        $examPin = ExamPin::where("exam_id", $item->id)->where("user_id", null)->where("status", "1")->limit($quantity)->get();

        if(count($examPin) != $quantity){
            return response(['success' => false, 'message' => "Sorry the requested Quantity is not available"]);
        }

        //check user Wallet
        $checkWallet = checkUserWallet($user, $price, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }
        $amount = $price;
        $balanceAction = userBalanceAction($user, $amount, $select_wallet_id, "-");
        if (!$balanceAction['success']) {
            return response(['success' => false, 'message' => $balanceAction['message'] ?? "Wallet balance error"]);
        }
        $wallet = $balanceAction['init_balance'];
        $balance = $balanceAction['final_balance'];
        $description = "$quantity pieces of $exam result checker";
        $Trx = strtoupper(Str::random(16));
        $ul['wallet_id'] = $select_wallet_id;
        $ul['user_level_id'] = $user->user_level_id;
        $ul['service'] = $item->name;
        $ul['service_type'] = "Result Checker";
        $ul['description'] = $description;
        $ul['amount'] = $amount;
        $ul['phone_number'] = $user->phone_number;
        $ul['type'] = "0";
        $ul['initial_balance'] = $wallet;
        $ul['final_balance'] = $balance;
        $ul['reference'] = $Trx;
        $ul['status'] = "pending";
        $tansaction =  $user->transaction()->create($ul);


        foreach ($examPin as  $value) {
            $value->user_id = $user->id;
            $value->status = "0";
            $value->tranx_ref = $Trx;
            $value->save();
        }

        //check if success and Update Record
        if ($tansaction) {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "success"]);
            if ($query) {
                $description." Reference ID Is : #$Trx";

                if (getSiteSettings("email_notify") == "Yes") {

                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Result Checker', $description);
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
                    'data' => [
                        'service' => "Result Checker",
                        'Exam' => $item->name,
                        'amount' => $amount,
                        'quantity' => $quantity,
                        'phone_number' => $user->phone_number,
                        'date' => $date,
                        'message' => $TranxMessage ?? $description,
                        'reference' => $Trx,
                        'wallet_balance' => $balance,
                    ],
                ]);
            }
        } else {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful"]);
            if ($query) {
                //refund user
                $refundAction = userBalanceAction($user, $amount, $select_wallet_id, "+");
                if (!empty($refundAction['success'])) {
                    $description." Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['service'] = $item->name;
                    $ul['service_type'] = "Result Checker";
                    $ul['description'] = $description;
                    $ul['amount'] = $amount;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $balance;
                    $ul['final_balance'] = $wallet;
                    $ul['reference'] = $trx;
                    $ul['status'] = "refund";
                    $tansaction =  $user->transaction()->create($ul);
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


}
