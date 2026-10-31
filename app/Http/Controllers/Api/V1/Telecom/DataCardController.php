<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\Telecom\DataCard;
use App\Models\Telecom\DataCardLog;
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

class DataCardController extends Controller
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
     *          Buy Data Card E-Pin
     *
     *
     */
    public function getNetwork()
    {
        $item = Network::where("status", "1")
            ->with("dataCardPlan")
            ->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No available Data"]);
        }

        return response(['success' => true, "data" => $item]);
    }



    public function buyDataCard(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'wallet_id' => '',
            'name' => 'required',
            'quantity' => 'required',
            'network_id' => 'required',
            'plan_id' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();
        $finalStatus = "pending";
        $quantity = request('quantity');

        if($quantity < 1){
            return response(['success' => false, 'message' => "Opps you can generate such data pin"]);
        }

        if($quantity > 10){
            return response(['success' => false, 'message' => "Opps maximum of 10 data pin at a time"]);
        }

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

        $select_wallet_id = $request->wallet_id ?? "1";
       // if (!checkWalletEnable("DataCard", $select_wallet_id)) {
        if ($select_wallet_id != "1") {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }


        $dataPlan = DataCard::where("id", $request->plan_id)
        ->where("network_id", $request->network_id)
        ->first();

        if (!$dataPlan) {
            return response(['success' => false, 'message' => "Plan Does not Exist"]);
        }


        if ($dataPlan->networks->status == "0" || $dataPlan->status == "0") {
            return response(['success' => false, 'message' => "Plan Is Currently not Available try again later"]);
        }


        $existingDumpPins = DataCardLog::pluck('pin')->toArray();
        $dumpPinArray = Str::of($dataPlan->dump_pin)->explode("\n")->map(function ($item) {
         return trim($item);
        })->toArray();

        $availableDumpPins = array_diff($dumpPinArray, $existingDumpPins);
        if(count($availableDumpPins) < 1 || count($availableDumpPins) < $quantity){
            return response(['success' => false, 'message' => "Data Pin unavailable try again later"]);
        }
        $randomPin = collect($availableDumpPins)->random($quantity);

        if(count($randomPin) < 1){
            return response(['success' => false, 'message' => "Data Pin unavailable try again later"]);
        }


        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api") {

            // $userTransaction = $user->transaction()->latest()->first();
            // if ($userTransaction) {
            //     if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  $dataPlan->networks->name) {
            //         $tt = Carbon::parse($userTransaction->created_at)->diffForHumans();
            //         return response(['success' => false, 'message' => "Probably Duplicate Transaction $tt try again later",]);
            //     }
            // }

        }

        $amount = $dataPlan->price * $quantity;

        //check offer
        $checkOffer = Offer::where("service_type", "DATACARD")->where("plan_id", $dataPlan->id)->where("user_level_id", $user->user_level_id)->where("status", "1")->first();
        if($checkOffer){
           $amount = $checkOffer->amount;
        }

        //check user Wallet
        $checkWallet =  checkUserWallet($user, $amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }

        $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
        $init_balance = $balanceAction["init_balance"];
        $final_balance = $balanceAction["final_balance"];

        if (!$balanceAction["success"]) {
            return response(['success' => false, 'message' => $balanceAction["message"] ?? "Wallet balance error"]);
        }

        if ($balanceAction["success"]) {
            $description = $quantity." ".$dataPlan->networks->name . " " . $dataPlan->plan." Data E-Pin";
            $Trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $user->user_level_id;
            $ul['service'] = $dataPlan->networks->name;
            $ul['service_plan_id'] = $request->plan_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service_type'] = "Data Card";
            $ul['plan_size'] = $dataPlan->size;
            $ul['real_price'] = $dataPlan->real_price;
            $ul['charge'] = $amount - ($dataPlan->real_price *$quantity);
            $ul['description'] = $description;
            $ul['amount'] = $amount;
            $ul['phone_number'] = $request->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $init_balance;
            $ul['final_balance'] = $final_balance;
            $ul['reference'] = $Trx;
            $ul['created_at'] = $date;
            $ul['status'] = "success";
            $tansaction =    $user->transaction()->create($ul);

            foreach ($randomPin as $value) {
            $pinLog['pin'] = $value;
            $pinLog['name'] = request('name');
            $pinLog['amount'] = $dataPlan->price;
            $pinLog['reference'] = $Trx;
            $pinLog['created_at'] = $date;
            $user->dataCardLog()->create($pinLog);
            }

        } else {
            return response(['success' => false, 'message' => "Wallet balance error"]);
        }

        if (getSiteSettings("email_notify") == "Yes") {

            $maskedPhone = substr($request->phone_number, 0, 4) . '***' . substr($request->phone_number, -3);
            $formattedDate = now()->format('d-M-Y h:i A');
            $formattedAmount = number_format($amount, 2);
            $formattedBalance = number_format($final_balance, 2);
            $networkName = $dataPlan->networks->name;
            $transactionRef = $Trx;
            $transactionType = "Data Card Purchase";
        
            $message = "
                <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong>. The details are shown below:</p>
        
                <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                        <td style='padding:8px; border:1px solid #ddd;'>DEBIT ({$transactionType})</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Network</td>
                        <td style='padding:8px; border:1px solid #ddd;'><strong>{$networkName}</strong></td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Quantity</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$quantity} pin(s)</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Phone Number (optional)</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$maskedPhone}</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Total Amount</td>
                        <td style='padding:8px; border:1px solid #ddd;'>₦{$formattedAmount}</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Transaction Reference</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$transactionRef}</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Plan</td>
                        <td style='padding:8px; border:1px solid #ddd;'>{$dataPlan->plan}</td>
                    </tr>
        
                    <tr>
                        <td style='padding:8px; border:1px solid #ddd;'>Date & Time</td>
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
                $this->sendMail($user->email, $user->profile->first_name, 'Data Card Purchase', $message);
            } catch (\Throwable $th) {
                // handle error silently
            }
        }

        (new FirebaseUserUpdate)->addUserNotification($user,
        [
            "message" => $description,
            "type" => "transaction",
            "read" => 0,
            "data" => [
                "service" => 'Data Card',
                "id" => $tansaction->id,
                "reference_id" => $Trx,
                "status" => "success",
                "extra" => [
                    'plan' => $dataPlan->plan,
                    'load_code' => $dataPlan->load_code,
                    'pin' => $randomPin,
                    'reference' => $Trx,
                    'network' => $dataPlan->networks->name,
                ],
                'date' => $date
            ]
        ]
        );

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

        return response()->json([
            'success' => true,
            'code' => 200,
            'status' => 'successful',
            'data' => [
                'service' => "Data Card",
                'amount' => $amount,
                'plan' => $dataPlan->plan,
                'load_code' => $dataPlan->load_code,
                'phone_number' => $request->phone_number,
                'date' => $date,
                'message' => $description,
                'pin' => $randomPin,
                'reference' => $Trx,
                'network' => $dataPlan->networks->name,
                'wallet_balance' => $final_balance,
            ],
            'user' => $user
        ]);



    }
}
