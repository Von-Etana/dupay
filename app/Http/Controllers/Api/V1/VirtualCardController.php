<?php

namespace App\Http\Controllers\Api\V1;

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
use App\Models\VirtualCardUSD;
use App\Jobs\CreateUsdVirtualCardJob;
use App\Models\VirtualCardNGN;
use App\Models\VirtualCardFundingCharge;
use Illuminate\Support\Facades\DB;
use App\Models\VirtualCardExchangeRate;


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

    public function getVirtualCardTransactions($id, $currency)
    {
        $validator = Validator::make(
            ['id' => $id, 'currency' => $currency],
            [
                'id' => 'required|integer',
                'currency' => 'required|string|in:NGN,USD',
            ]
        );
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
    
        $user = Auth::user();
    
        try {
            $response = null;
    
            switch ($currency) {
                case 'NGN':
                    $card = VirtualCardNGN::where('id', $id)
                        ->where('user_id', $user->id)
                        ->first();
    
                    if (!$card) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Card not found.',
                        ], 404);
                    }
    
                    $response = getVirtualNairaCardATMHistory($card->card_id);
    
                    if (empty($response) || !($response['success'] ?? false)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No transaction found for this card.',
                            'data' => [],
                        ]);
                    }
                    break;
    
                case 'USD':
                    $card = VirtualCardUSD::where('id', $id)
                        ->where('user_id', $user->id)
                        ->first();
    
                    if (!$card) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Card not found.',
                        ], 404);
                    }
    
                    $payload = [
                        'card_id' => $card->card_id,
                        'public_key' => env('STROWALLET_PUBLIC_KEY'),
                    ];
    
                    $response = getCardTransactions($payload);
    
                    if (empty($response) || !($response['success'] ?? false)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No transaction found for this card.',
                            'data' => [],
                        ]);
                    }
                    break;
    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported currency type.',
                    ], 422);
            }
    
            // âœ… Optionally update Firebase user info
            (new FirebaseUserUpdate)->updateUser($user);
    
            return response()->json([
                'success' => true,
                'message' => 'Transactions fetched successfully.',
                'data' => $response['data'] ?? [],
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error fetching virtual card transactions', [
                'user_id' => $user->id,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch card transactions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        $date = Carbon::now();
    
        $validatedData = Validator::make($request->all(), [
            'currency' => 'required|in:NGN,USD',
        ]);
    
        if ($validatedData->fails()) {
            return response([
                'success' => false,
                'message' => implode(', ', $validatedData->errors()->all()),
            ]);
        }
    
        $user = Auth::user();
        $cardType = strtoupper($request->currency);
        
        // Transaction PIN verification
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        if (!$user->hasVerifyWallet) {
            return response(['success' => false, 'message' => "Account not verified"]);
        }

        // USD cards are issued via the NFC endpoint, whose id_type must be a
        // government ID (national_id / passport / drivers_license). We only
        // support NIN here, so require the user's wallet KYC to be NIN-based.
        if ($cardType === 'USD'
            && strtoupper(optional($user->walletVerification)->method ?? '') !== 'NIN') {
            return response([
                'success' => false,
                'message' => 'A verified NIN is required to create a USD card. Please complete NIN verification, then try again.',
            ]);
        }

        DB::beginTransaction();
    
        try {
            // NOTE: USD customer creation (bitvcard/create-user) used to run
            // here synchronously and blocked the request past the app's timeout
            // for brand-new accounts. It now happens inside
            // CreateUsdVirtualCardJob together with card creation, so store()
            // returns immediately. NGN still creates its customer synchronously.

            if ($cardType === 'NGN' && !$user->strowallet_customer_id) {
                $profile = $user->profile;
                $wallet = $user->walletVerification;
    
                $required = [
                    'nin' => $wallet->bvn,
                    'dob' => $wallet->date_of_birth,
                    'line1' => $wallet->address,
                    'city' => $wallet->city,
                    'state' => $wallet->state,
                    'firstname' => $profile->first_name,
                    'lastname' => $profile->last_name,
                ];
    
                $missingFields = array_keys(array_filter($required, fn($v) => empty($v)));
                if ($missingFields) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Missing required user data.',
                        'missing_fields' => $missingFields,
                    ]);
                }
    
                $data = [
                    'public_key' => env('STROWALLET_PUBLIC_KEY'),
                    'firstname'  => $profile->first_name,
                    'lastname'   => $profile->last_name,
                    'email'      => $user->email,
                    'phone'      => '234' . ltrim($user->phone_number, '0'),
                    'nin'        => $wallet->bvn,
                    'dob'        => Carbon::parse($wallet->date_of_birth)->format('Y/m/d'),
                    'name'       => $profile->first_name . ' ' . $profile->last_name,
                    'line1'      => $wallet->address,
                    'city'       => $wallet->city,
                    'state'      => $wallet->state,
                ];
    
                $resp = createNairaCardUser($data, $user);
    
                if (empty($resp['status'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $resp['message'] ?? 'Failed to create Naira card user.',
                    ]);
                }
            }
    
            if (getSiteSettings("enable_virtual_card_creation") !== "Yes") {
                DB::rollBack();
                return response(['success' => false, 'message' => "Virtual card creation is temporarily disabled."]);
            }
    
            if (VirtualCard::where('user_id', $user->id)->where('currency', $cardType)->exists()) {
                DB::rollBack();
                return response(['success' => false, 'message' => "You already have a $cardType card."]);
            }

            // USD cards are created via a background job, so an in-flight request
            // leaves a `pending` row. Guard against a double-tap charging twice
            // or spawning two cards.
            if ($cardType === 'USD'
                && VirtualCardUSD::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'active'])
                    ->exists()
            ) {
                DB::rollBack();
                return response(['success' => false, 'message' => "You already have a USD card or one is being created."]);
            }
    
            if ($cardType === "NGN") {
                $cardCreationFee = getSiteSettings("naira_card_creation_fee");
                $amount = floatval($cardCreationFee);
            } else {
                $cardCreationFee = floatval(getSiteSettings("dollar_card_creation_fee") ?? 0);
                $cardLoadAmount  = floatval(getSiteSettings("dollar_card_amount") ?? '3');
                $buyingRate      = floatval(getSiteSettings("buying_dollar_rate") ?? 1);
    
                $fundingAmount   = $cardLoadAmount * $buyingRate;
                $amount          = $cardCreationFee + $fundingAmount;
            }
    
            // Validate amount
            if ($amount <= 0) {
                DB::rollBack();
                return response(['success' => false, 'message' => "Invalid amount calculated"]);
            }
    
            $walletId = 1;
            if (!checkUserWallet($user, $amount, $walletId)) {
                DB::rollBack();
                return response(['success' => false, 'message' => "Insufficient Funds"]);
            }
    
            $balanceAction = userBalanceAction($user, $amount, $walletId, "-");
    
            if (empty($balanceAction['success'])) {
                DB::rollBack();
                return response(['success' => false, 'message' => "Unable to access wallet balance"]);
            }
    
            $init_balance  = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
            
            // Update Firebase
            (new FirebaseUserUpdate)->updateUser($user);
    
            $trx = strtoupper(Str::random(16));
            $description = $cardType === 'USD'
                ? "USD Virtual Card Creation Fee + Initial Load"
                : "NGN Virtual Card Creation Fee";
    
            // Create transaction record
            $transaction = $user->transaction()->create([
                'user_level_id'    => $user->user_level_id,
                'wallet_id'        => $walletId,
                'service'          => "System",
                'service_type'     => "Virtual Card",
                'description'      => $description,
                'amount'           => $amount,
                'phone_number'     => $user->phone_number,
                'type'             => "0", // Debit
                'initial_balance'  => $init_balance,
                'final_balance'    => $final_balance,
                'reference'        => $trx,
                'status'           => "success",
            ]);
    
            if ($cardType === 'USD') {
                // Async: persist a pending row and hand BOTH customer creation
                // and card creation to the background job. store() returns
                // immediately — no blocking on StroWallet.
                $pendingCard = VirtualCardUSD::create([
                    'user_id'   => $user->id,
                    'card_name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                    'card_type' => 'visa',
                    'reference' => $trx,
                    'status'    => 'pending',
                ]);

                DB::commit();

                CreateUsdVirtualCardJob::dispatchAfterResponse(
                    $pendingCard->id,
                    $amount,
                    $walletId,
                    $cardLoadAmount ?? '3'
                );

                return response([
                    'success'        => true,
                    'pending'        => true,
                    'message'        => 'Your USD card is being created. It will appear in your account shortly.',
                    'data'           => $user->virtualCard,
                    'amount_debited' => $amount,
                ]);
            }

            // ── NGN: synchronous ─────────────────────────────────────────────
            $res = createVirtualNairaCard(
                $user->id,
                $user->strowallet_customer_id,
                'AfriGo',
                'virtual'
            );

            if (empty($res["status"])) {
                if (getSiteSettings("enable_virtual_card_creation_refund") === "Yes") {
                    $refund = userBalanceAction($user, $amount, $walletId, "+");
                    $user->transaction()->create([
                        'user_level_id'   => $user->user_level_id,
                        'wallet_id'       => $walletId,
                        'service'         => "System",
                        'service_type'    => "Virtual Card",
                        'description'     => "$cardType Virtual Card Creation Refund",
                        'amount'          => $amount,
                        'phone_number'    => $user->phone_number,
                        'type'            => "1", // Credit
                        'initial_balance' => $refund["init_balance"],
                        'final_balance'   => $refund["final_balance"],
                        'reference'       => strtoupper(Str::random(16)),
                        'status'          => "refund",
                    ]);
                    (new FirebaseUserUpdate)->updateUser($user);
                }
                
                DB::rollBack();
                return response([
                    'success' => false,
                    'message' => "Unable to create card, try again later.",
                    'response' => $res,
                ]);
            }
    
            // Commit transaction if everything is successful
            DB::commit();
    
            return response([
                'success' => true,
                'message' => "Card Created Successfully",
                'data'    => $user->virtualCard,
                'amount_debited' => $amount, 
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Virtual Card Creation Error: ' . $e->getMessage());
            
            return response([
                'success' => false,
                'message' => "An error occurred while creating your card.",
            ]);
        }
    }

    public function storeOld(Request $request)
    {

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

        if (!$user->hasVerifyWallet) {
            return response(['success' => false, 'message' => "Kindly verify your account"]);
        }
        
        if($cardType == 'USD' && !$user->profile->strowallet_customer_id){
            $payload = [
                'public_key'     => env('STROWALLET_PUBLIC_KEY'),
                'houseNumber'    => rand(00, 11),
                'firstName'      => $user->profile->first_name,
                'lastName'       => $user->profile->last_name,
                'idNumber'       => $user->walletVerification->bvn,
                'customerEmail'  => $user->email,
                'phoneNumber'    => '234' . ltrim($user->phone_number, '0'),
                'dateOfBirth'    => Carbon::createFromFormat('Y-m-d', $user->walletVerification->date_of_birth)->format('Y-m-d'),
                'idImage'        => asset('storage/' . $user->walletVerification->id_image),
                'userPhoto'      => asset('storage/' . $user->walletVerification->user_photo),
                'line1'          => $user->walletVerification->address,
                'state'          => $user->walletVerification->state,
                'zipCode'        => $user->walletVerification->zip_code,
                'city'           => $user->walletVerification->city,
                'country'        => 'Nigeria',
                'idType'         => $user->walletVerification->method,
            ];
            
            $respos = newCustomer($payload, $user);
        }
        
        if ($cardType == 'NGN' && !$user->strowallet_customer_id) {
            
            $profile = $user->profile;
            $wallet = $user->walletVerification;
    
            $nin = $wallet->bvn;
            $dob = $wallet->date_of_birth;
            $line1 = $wallet->address;
            $city = $wallet->city;
            $state = $wallet->state;
            $firstname = $profile->first_name;
            $lastname = $profile->last_name;
    
            $missingFields = [];
    
            foreach ([
                'nin' => $nin,
                'dob' => $dob, 
                'line1' => $line1,
                'city' => $city, 
                'state' => $state,
                'firstname' => $firstname, 
                'lastname' => $lastname
            ] as $key => $value) {
                if (empty($value)) $missingFields[] = $key;
            }
    
            if (!empty($missingFields)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Missing required user data.',
                    'missing_fields' => $missingFields
                ]);
            }
    
            $parsedDob = Carbon::createFromFormat('Y-m-d', $dob)->format('Y/m/d');
    
            $data = [
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                'firstname'  => $firstname,
                'lastname'   => $lastname,
                'email'      => $user->email,
                'phone'      => '234' . ltrim($user->phone_number, '0'),
                'nin'        => $nin,
                'dob'        => $parsedDob,
                'name'       => $firstname . ' ' . $lastname,
                'line1'      => $line1,
                'city'       => $city,
                'state'      => $state,
            ];
    
            $respns = createNairaCardUser($data, $user);
    
            if (!$respns['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to create Naira card user.'
                ]);
            }
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

        if ($cardType == "NGN") {
            // NGN: user pays only card creation fee
            $cardCreationFee = getSiteSettings("naira_card_creation_fee");
            $amount          = $cardCreationFee;
        
        } elseif ($cardType == "USD") {
            // USD: user pays creation fee + load amount
            $cardCreationFee = getSiteSettings("dollar_card_creation_fee");
            $cardLoadAmount  = getSiteSettings("dollar_card_amount");
            $buyingRate = getSiteSettings("buying_dollar_rate");
            $fundingAmount = number_format(floatval($cardLoadAmount/$buyingRate),2);
            $amount          = $cardCreationFee + $fundingAmount;
        
        } else {
            return response(['success' => false, 'message' => "Unknown card request"]);
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


    //   $res =  (new iSmartFlutterWave)->createVirtualCard($user, $cardAmount, $cardType,$debitCurrency);
    if($request->currency === 'USD'){
       $data = [
            'name_on_card'   => $user->profile->first_name.' '.$user->profile->last_name,
            'card_type'      => 'visa',
            'public_key'     => env('STROWALLET_PUBLIC_KEY'),
            'amount'         => '3',
            'customerEmail'  => $user->email,
            // 'mode'           => 'sandbox',
        ];
    
        $res = newCard($data);
        
        return $res;
    }else{
        $res = createVirtualNairaCard(
            $user->id, 
            $user->strowallet_customer_id, 
            'AfriGo', 
            'virtual'
        );
    }

       if($res["status"] != true){

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

    // public function fetchCard($currency)
    // {
    //     $validator = Validator::make(
    //         ['currency' => $currency],
    //         [
    //             'currency' => 'required|string|in:NGN,USD',
    //         ]
    //     );
    
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $validator->errors()->first(),
    //         ], 422);
    //     }
    
    //     $user = Auth::user();
    
    //     try {
    //         switch ($currency) {
    //             case 'NGN':
    //                 $card = VirtualCardNGN::where('status', 'active')
    //                     ->where('user_id', $user->id)
    //                     ->firstOrFail();
    
    //                 $response = getVirtualNairaCard($card->card_id);
    
    //                 return response()->json([
    //                     'success' => (bool) ($response['status'] ?? false),
    //                     'message' => $response['message'] ?? 'NGN card fetched successfully.',
    //                     'card_status' => $card->status,
    //                     'data' => $response['data'] ?? [],
    //                 ]);
    
    //             case 'USD':
    //                 $card = VirtualCardUSD::where('status', 'active')
    //                     ->where('user_id', $user->id)
    //                     ->firstOrFail();
    
    //                 $payload = [
    //                     'card_id' => $card->card_id,
    //                     'public_key' => env('STROWALLET_PUBLIC_KEY'),
    //                 ];
    
    //                 $response = getCardDetails($payload);
    
    //                 return response()->json([
    //                     'success' => (bool) ($response['status'] ?? false),
    //                     'message' => $response['message'] ?? 'USD card fetched successfully.',
    //                     'card_status' => $card->status,
    //                     'data' => $response['data'] ?? [],
    //                 ]);
    
    //             default:
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Unsupported currency type.',
    //                 ], 422);
    //         }
    
    //     } catch (ModelNotFoundException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Card not found or does not belong to you.',
    //         ], 404);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch card details.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    
    
    public function fetchCard($currency)
    {
        $validator = Validator::make(
            ['currency' => $currency],
            [
                'currency' => 'required|string|in:NGN,USD',
            ]
        );
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
    
        $user = Auth::user();
    
        try {
            switch ($currency) {
                case 'NGN':
                    $card = VirtualCardNGN::where('user_id', $user->id)
                        ->first();
    
                    if (!$card) {
                        return response()->json([
                            'success' => true,
                            'message' => 'You have not created an NGN card yet.',
                            'error' => 'not_found',
                            'data' => null,
                        ], 200);
                    }
    
                    // card_id can be null right after creation (or if the
                    // provider didn't return one). getVirtualNairaCard() type-
                    // hints a string, so calling it with null throws a
                    // TypeError -> 500 -> the app drops the card and stays on
                    // "Claim". Only call it when we actually have an id.
                    $response = !empty($card->card_id)
                        ? getVirtualNairaCard($card->card_id)
                        : ['status' => false, 'data' => null];

                    return response()->json([
                        'success' => true,
                        'message' => "NGN card fetched successfully.",
                        'card_status' => $card->status,
                        'data' => [
                            'balance' => $card->balance,
                            'id' => $card->id,
                            'card_name' => $card->card_name,
                            'card_number' => $card->card_number ?? NULL,
                            'cvv' => $card->cvv ?? NULL,
                            'card_type' => $card->card_type ?? NULL,
                            'billing_address' => $card->billing_address,
                            'billing_city' => $card->billing_city,
                            'billing_state' => $response['data']['billing_address']['state'] ?? $card->billing_state,
                            'expiry' => $card->expiry,
                            'expiry_year'=> 2028,
                            'expiry_month'=>10, 
                            'default_pin' =>null,
                              'type' => 'NGN', 

                            
                            'card_brand' => 'Afrigo',
                            'last4'=> null,
                            'billing_country'=>'NG', 
                            'billing_zip_code'=>12312, 
                            'card_holder_name'=>$user->name, 
                            
                            
                            
                            
                            
                            'status' => $card->status,
                        ],
                        'response_data' => $response['data'] ?? null,
                    ]);

                case 'USD':
                    $card = VirtualCardUSD::where('status', 'active')
                        ->where('user_id', $user->id)
                        ->first();
    
                    if (!$card) {
                        return response()->json([
                            'success' => true,
                            'message' => 'You have not created a USD card yet.',
                            'error' => 'not_found',
                            'data' => null,
                        ], 200);
                    }
    
                    $payload = [
                        'card_id' => $card->card_id,
                        'public_key' => env('STROWALLET_PUBLIC_KEY'),
                    ];
    
                    $response = getCardDetails($payload);

                    // The new card issuer changed the fetch-card-detail shape and
                    // no longer returns billing_country / billing_zip_code, which
                    // were read directly below and crashed with "Undefined array
                    // key". Default to an empty array so no missing key can 500.
                    $cardData = $response['data'] ?? [];

                    // Card display details (number, CVV, expiry, holder, balance)
                    // come from the ISSUER's live response — our local row only
                    // stores card_id + status. Read from $cardData first, fall
                    // back to the local column. Parse expiry "MM/YYYY".
                    $exp = $cardData['expiry'] ?? $card->expiry;
                    $expMonth = 10;
                    $expYear = 2028;
                    if (is_string($exp) && strpos($exp, '/') !== false) {
                        [$m, $y] = array_pad(explode('/', $exp), 2, null);
                        if ((int) $m) $expMonth = (int) $m;
                        if ((int) $y) $expYear = (int) $y;
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $response['message'] ?? 'USD card fetched successfully.',
                        'card_status' => $card->status,
                        'data' => [
                            'balance' => $cardData['balance'] ?? $card->balance,
                            'id' => $card->id,
                            'card_name' => $cardData['card_name'] ?? $card->card_name,
                            'card_number' => $cardData['card_number'] ?? $card->card_number,
                            'cvv' => $cardData['cvv'] ?? $card->cvv,
                            'card_type' => $cardData['card_type'] ?? $card->card_type ?? 'visa',
                            'billing_address' => $cardData['billing_address'] ?? $card->billing_address,
                            'billing_city' => $cardData['billing_city'] ?? $card->billing_city,
                            'billing_state' => $cardData['billing_state'] ?? $card->billing_state ?? 'florida',
                            'expiry' => $exp,
                            'status' => $card->status,
                            'expiry_year'=> $expYear,
                            'expiry_month'=> $expMonth,
                            'default_pin' =>null,
                            'type' => 'USD' ,
                            'card_brand' => $cardData['card_brand'] ?? $card->card_brand ?? 'visa',
                            'last4'=> $cardData['last4'] ?? null,
                            'billing_country' => $cardData['billing_country'] ?? 'US',
                            'billing_zip_code' => $cardData['postal'] ?? $cardData['billing_zip_code'] ?? $card->billing_zip_code ?? null,
                            'card_holder_name'=> $cardData['card_holder_name'] ?? $cardData['card_name'] ?? $user->name,
                            // New issuer returns secure iframe URLs for the PAN/CVV
                            // instead of raw values — pass them through so the app
                            // can render them (null-safe if not present).
                            'card_number_url' => $cardData['card_number_url'] ?? null,
                            'cvv_url' => $cardData['cvv_url'] ?? null,
                        ],
                        'response_data' => $response['data'] ?? null,
                    ]);
    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported currency type.',
                    ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching card details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    public function fundVirtualCard(Request $request)
    {
        $date = Carbon::now();
    
        $validated = Validator::make($request->all(), [
            'currency' => 'required|string|in:NGN,USD,EUR',
            'amount'   => 'required|numeric|min:0.01',
            'card_id'  => 'required|string',
        ]);
    
        if ($validated->fails()) {
            return response()->json([
                'success' => false, 
                'message' => implode(' ', $validated->errors()->all())
            ], 422);
        }
    
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthenticated.'
            ], 401);
        }
    
        if (!$user->hasVerifyWallet) {
            return response()->json([
                'success' => false, 
                'message' => "Kindly verify your account"
            ], 403);
        }
        
        // Transaction pin validation
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $fundingCurrency = strtoupper($request->currency);
        $inputAmountSource = floatval($request->amount);
        
        if ($inputAmountSource <= 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid amount.'
            ], 422);
        }
    
        $card = VirtualCardUSD::where('id', $request->card_id)->first();
        if (!$card) {
            return response()->json([
                'success' => false, 
                'message' => 'Card not found'
            ], 404);
        }
    
        if (getSiteSettings("enable_virtual_dollar_card") !== "Yes") {
            return response()->json([
                'success' => false, 
                'message' => "Sorry, virtual card is disabled at the moment."
            ], 403);
        }
    
        $exchange = VirtualCardExchangeRate::where('currency', "{$fundingCurrency}-USD")->first();
        if (!$exchange) {
            return response()->json([
                'success' => false, 
                'message' => "Exchange rate for {$fundingCurrency}-USD not configured."
            ], 500);
        }
    
        $rate = floatval($exchange->rate);
        $feePercentage = floatval($exchange->fee);
    
        if ($rate <= 0) {
            return response()->json([
                'success' => false, 
                'message' => "Invalid exchange rate configured for {$fundingCurrency}-USD."
            ], 500);
        }
    
        if ($feePercentage < 0 || $feePercentage > 100) {
            return response()->json([
                'success' => false, 
                'message' => "Invalid fee percentage configured for {$fundingCurrency}-USD."
            ], 500);
        }
    
        $convertedUSD = round($inputAmountSource / $rate, 2);
    
        if ($convertedUSD < 3) {
            return response()->json([
                'success' => false,
                'message' => "Minimum virtual card funding amount is 3 USD. Your {$inputAmountSource} {$fundingCurrency} equals {$convertedUSD} USD."
            ], 422);
        }
    
        $feeUsd = round($convertedUSD * ($feePercentage / 100), 2);
        // Apply cap (in USD) if configured
        $feeCap = isset($exchange->cap) ? floatval($exchange->cap) : 0;
        if ($feeCap > 0 && $feeUsd > $feeCap) {
            $feeUsd = $feeCap;
        }
        
        $feeInSourceCurrency = round($feeUsd * $rate, 2);
    
        $debitAmountInSourceCurrency = round($inputAmountSource, 2);
        $totalDebitInSourceCurrency = round($debitAmountInSourceCurrency + $feeInSourceCurrency, 2);
    
        switch ($fundingCurrency) {
            case 'NGN':
                $walletBalance = $user->balance;
                $walletField = 'balance';
                $walletId = 1;
                break;
            case 'USD':
                $walletBalance = $user->usd_balance;
                $walletField = 'usd_balance';
                $walletId = 1;
                break;
            case 'EUR':
                $walletBalance = $user->eur_balance;
                $walletField = 'eur_balance';
                $walletId = 1;
                break;
            default:
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid currency selected.'
                ], 422);
        }
    
        if ($walletBalance < $totalDebitInSourceCurrency) {
            return response()->json([
                'success' => false, 
                'message' => "Insufficient funds in {$fundingCurrency} wallet."
            ], 422);
        }
    
        DB::beginTransaction();
        try {
            $initialBalance = $walletBalance;
            $finalBalance = $walletBalance - $totalDebitInSourceCurrency;
            $user->update([$walletField => $finalBalance]);
    
            $trx = strtoupper(Str::random(16));
            $description = "{$convertedUSD} USD Virtual Card Funding (Debit: {$totalDebitInSourceCurrency} {$fundingCurrency}, Fee: {$feeInSourceCurrency} {$fundingCurrency} - {$feePercentage}%)";
    
            $txPayload = [
                'user_level_id'    => $user->user_level_id,
                'wallet_id'        => $walletId,
                'service'          => "System",
                'service_type'     => "Virtual Card",
                'description'      => $description,
                'amount'           => $totalDebitInSourceCurrency,
                'charge'           => $feeInSourceCurrency,
                'phone_number'     => $user->phone_number,
                'type'             => "0", // Debit
                'initial_balance'  => $initialBalance,
                'final_balance'    => $finalBalance,
                'reference'        => $trx,
                'status'           => "pending",
                'currency'         => $fundingCurrency,
            ];
    
            $fundingTransaction = $user->transaction()->create($txPayload);
            (new FirebaseUserUpdate)->updateUser($user);
    
            $payload = [
                'card_id'    => $card->card_id,
                'amount'     => $convertedUSD,
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
            ];
            
            $fundRes = fundVCard($payload);
    
            if (!isset($fundRes['success']) || $fundRes['success'] !== true) {
                $msg = $fundRes['message'] ?? 'Unable to fund card, try again later';
    
                if (getSiteSettings("enable_virtual_card_funding_refund") === "Yes") {
                    // Refund the user
                    $user->update([$walletField => $initialBalance]);
    
                    $refundTrx = strtoupper(Str::random(16));
                    $refundDesc = "{$convertedUSD} USD Virtual Card Funding Refund - {$msg}";
                    $refundPayload = [
                        'user_level_id'   => $user->user_level_id,
                        'wallet_id'       => $walletId,
                        'service'         => "System",
                        'service_type'    => "Virtual Card",
                        'description'     => $refundDesc,
                        'amount'          => $totalDebitInSourceCurrency,
                        'phone_number'    => $user->phone_number,
                        'type'            => "1", // Credit
                        'initial_balance' => $finalBalance,
                        'final_balance'   => $initialBalance,
                        'reference'       => $refundTrx,
                        'status'          => "refund",
                        'currency'        => $fundingCurrency,
                    ];
    
                    $refundTransaction = $user->transaction()->create($refundPayload);
                    (new FirebaseUserUpdate)->updateUser($user);
    
                    (new FirebaseUserUpdate)->addUserNotification($user, [
                        "message" => $refundDesc,
                        "type" => "transaction",
                        "read" => 0,
                        "data" => [
                            "service" => 'Virtual Card Funding',
                            "id" => $refundTransaction->id,
                            "reference_id" => $refundTrx,
                            "status" => "refund",
                            'date' => $date
                        ]
                    ]);
    
                    if (getSiteSettings("email_notify") === "Yes") {
                        try {
                            $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Funding Refund', $refundDesc);
                        } catch (\Throwable $e) {
                            // Log email error if needed
                            \Log::error('Email notification failed: ' . $e->getMessage());
                        }
                    }
                }
    
                DB::commit();
                return response()->json([
                    'success' => false, 
                    'message' => 'Unable to fund card, try again later',
                    'api_message' => $msg,
                ], 422);
            }
    
            $getPayload = [
                'card_id' => $card->card_id,
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
            ];
            $res = getCardDetails($getPayload);
            if (isset($res['status']) && $res['status']) {
                try {
                    $data = $res['data'] ?? null;
                    if ($data) {
                        $card->update(["balance" => $data->balance ?? $card->balance]);
                    }
                } catch (\Throwable $e) {
                    \Log::error('Failed to update card balance: ' . $e->getMessage());
                }
            }
    
            $user->transaction()->where('id', $fundingTransaction->id)->update(['status' => 'success']);
    
            (new FirebaseUserUpdate)->addUserNotification($user, [
                "message" => $description,
                "type" => "transaction",
                "read" => 0,
                "data" => [
                    "service" => 'Virtual Card Funding',
                    "id" => $fundingTransaction->id,
                    "reference_id" => $trx,
                    "status" => "success",
                    'date' => $date
                ]
            ]);
    
            // Send email notification
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $formattedDate = Carbon::now()->format('d-M-Y h:i A');
                    $maskedCardId = substr($card->card_id, 0, 4) . "XX..XX" . substr($card->card_id, -4);
    
                    $message = "
                    <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong>:</p>
                    <table style='width:100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Transaction Type</td>
                            <td style='padding:8px; border:1px solid #ddd;'>DEBIT ALERT</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Amount</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$totalDebitInSourceCurrency} {$fundingCurrency}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Card Funding Amount</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$convertedUSD} USD</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Fee ({$feePercentage}%)</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$feeInSourceCurrency} {$fundingCurrency}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Card ID</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$maskedCardId}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Narration</td>
                            <td style='padding:8px; border:1px solid #ddd;'>Funding of Virtual USD Card</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Reference</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$trx}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'>Date</td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$formattedDate}</td>
                        </tr>
                    </table>
                    <br><p>Thank you for using <strong>Dupay</strong>.</p>
                    ";
                    $this->sendMail($user->email, $user->profile->first_name, 'Dupay Debit Transaction Notification', $message);
                } catch (\Throwable $e) {
                    \Log::error('Email notification failed: ' . $e->getMessage());
                }
            }
    
            (new FirebaseUserUpdate)->updateUser($user);
            DB::commit();
    
            return response()->json([
                'success' => true,
                'code' => 200,
                'status' => 'successful',
                'message' => "Card Funded Successfully",
                'data' => [
                    'service' => "Virtual Card Funding",
                    'funding_currency' => $fundingCurrency,
                    'funding_amount_source' => $inputAmountSource,
                    'converted_usd' => $convertedUSD,
                    'fee_percentage' => $feePercentage,
                    'fee_usd' => $feeUsd,
                    'fee_in_source_currency' => $feeInSourceCurrency,
                    'debit_amount' => $debitAmountInSourceCurrency,
                    'total_debit' => $totalDebitInSourceCurrency,
                    'wallet_balance' => $finalBalance,
                    'reference' => $trx,
                    'date' => $date,
                ],
                'card' => $card,
                'user' => $user->makeHidden(['transaction_pin', 'password']) 
            ]);
    
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Virtual card funding error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
    
    public function rates()
    {
        $rates = VirtualCardExchangeRate::all();
        return response()->json([
            'success'  => true,
            'message' => 'Conversion successful.',
            'data'    => $rates,
        ]);
    }

    
    //fund card
    // public function fundVirtualCard(Request $request){


    //         $date = Carbon::now();
    //         $data = $request->all();
    //         $validatedData  = Validator::make($data, [
    //             'currency' => 'required',
    //             'amount' => 'required',
    //             'card_id' => 'required',
    //         ]);

    //         if ($validatedData->fails()) {
    //             $error = "";
    //             foreach ($validatedData->errors()->all() as $key => $value) {
    //                 $error .= $value;
    //             }
    //             return response(['success' => false, 'message' => $error]);
    //         }

    //         $user = Auth::user();


    //         $cardType = $request->currency;
    //         $cardId = $request->card_id;

    //         //check if user is verify
    //         if (!$user->hasVerifyWallet) {
    //             return response(['success' => false, 'message' => "Kindly verify your account"]);
    //         }



    //         $card = VirtualCardUSD::where('card_id', $cardId)->first();

    //         if(!$card){
    //             return response(['success' => false, 'message' => "card not found"]);
    //         }

    //         if($cardType != "NGN" && $cardType != 'USD'){
    //             return response(['success' => false, 'message' => "Unknown card request"]);
    //         }

    //         $requestAmount = str_replace(',', '',$request->amount);

    //         if($cardType == "NGN"){
    //             if (getSiteSettings("enable_virtual_naira_card") != "Yes") {
    //                 return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
    //             }


    //             // $debitCurrency = getSiteSettings("naira_card_debit_currency");
    //             // $fundingAmount = $requestAmount;
    //             // $charge = getSiteSettings("naira_virtual_card_funding_charge");
    //             // $amount = $requestAmount+$charge;
    //         }else{
    //             if (getSiteSettings("enable_virtual_dollar_card") != "Yes") {
    //                 return response(['success' => false, 'message' => "Sorry virtual card is disabled at the moment try again later"]);
    //             }
    //             $buyingRate = getSiteSettings("buying_dollar_rate");
    //             $debitCurrency = getSiteSettings("dollar_card_debit_currency");
    //             $fundingAmount = number_format(floatval($requestAmount/$buyingRate),2);
    //             if ($fundingAmount < floatval(2)) {
    //                 return response(['success' => false, 'message' => " Minimum funding of 3 USD "]);
    //             }
    //             $charge = 0;
    //             $amount = $requestAmount;
    //         }

    //         $select_wallet_id = "1";
    //         $checkWallet =  checkUserWallet($user,$amount, $select_wallet_id);
    //         if (!$checkWallet) {
    //             return response(['success' => false, 'message' => "Insufficient Funds"]);
    //         }

    //         $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"-");
    //         $init_balance = $balanceAction["init_balance"];
    //         $final_balance = $balanceAction["final_balance"];
    //         (new FirebaseUserUpdate)->updateUser($user);

    //         if($balanceAction["success"] != true){
    //             return response(['success' => false, 'message' => "Unable to access wallet balance"]);
    //         }

    //                     $description = "$fundingAmount $request->currency Virtual Card Funding";
    //                     $trx = strtoupper(Str::random(16));
    //                     $ul['user_level_id'] = $user->user_level_id;
    //                     $ul['wallet_id'] = $select_wallet_id;
    //                     $ul['service'] = "System";
    //                     $ul['service_type'] = "Virtual Card";
    //                     $ul['description'] = $description;
    //                     $ul['amount'] = $requestAmount;
    //                     $ul['charge'] = $charge;
    //                     $ul['phone_number'] = $user->phone_number;
    //                     $ul['type'] = "0";
    //                     $ul['initial_balance'] = $init_balance;
    //                     $ul['final_balance'] = $final_balance;
    //                     $ul['reference'] = $trx;
    //                     $ul['status'] = "pending";
    //                     $Fundingtansaction =  $user->transaction()->create($ul);



    //                     // $fundRes =  (new iSmartFlutterWave)->fundVirtualCard($cardId,$fundingAmount,$debitCurrency);
    //                     // $fundRes = topUpVirtualCard($cardId, $fundingAmount);
                        
    //                     if($cardType == 'USD'){
    //                         $payload = [
    //                             'card_id' => $card->card_id,
    //                             'amount' => $fundingAmount,
    //                             'public_key' => env('STROWALLET_PUBLIC_KEY'),
    //                             // 'mode' => 'sandbox',
    //                         ];
    //                         $fundRes = fundVCard($payload);
    //                     }else{
                            
    //                     }

    //                     if($fundRes["success"] != true){

    //                      //if auto refund card
    //                      if (getSiteSettings("enable_virtual_card_funding_refund") == "Yes") {
    //                                  $balanceAction =  userBalanceAction($user,$amount, $select_wallet_id,"+");
    //                                  $init_balance = $balanceAction["init_balance"];
    //                                  $final_balance = $balanceAction["final_balance"];
    //                                  $user->transaction()->where(['id' => $Fundingtansaction->id])->update(['status' => 'unsuccessful']);
    //                                  $description = "$fundingAmount $request->currency Virtual Card Funding Refund";
    //                                  $trx = strtoupper(Str::random(16));
    //                                  $ul['user_level_id'] = $user->user_level_id;
    //                                  $ul['wallet_id'] = $select_wallet_id;
    //                                  $ul['service'] = "System";
    //                                  $ul['service_type'] = "Virtual Card";
    //                                  $ul['description'] = $description;
    //                                  $ul['amount'] = $amount;
    //                                  $ul['phone_number'] = $user->phone_number;
    //                                  $ul['type'] = "1";
    //                                  $ul['initial_balance'] = $init_balance;
    //                                  $ul['final_balance'] = $final_balance;
    //                                  $ul['reference'] = $trx;
    //                                  $ul['status'] = "refund";
    //                                  $tansaction =  $user->transaction()->create($ul);
    //                                  (new FirebaseUserUpdate)->updateUser($user);
    //                                  (new FirebaseUserUpdate)->addUserNotification($user,
    //                                  [
    //                                      "message" => $description,
    //                                      "type" => "transaction",
    //                                      "read" => 0,
    //                                      "data" => [
    //                                          "service" => 'Virtual Card Funding',
    //                                          "id" => $tansaction->id,
    //                                          "reference_id" => $trx,
    //                                          "status" => "refund",
    //                                          'date' => $date
    //                                      ]
    //                                  ]
    //                                  );
    //                                  if (getSiteSettings("email_notify") == "Yes") {
    //                                     try {
    //                                         $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Funding', $description);
    //                                     } catch (\Throwable $th) {
    //                                         //throw $th;
    //                                     }
    //                                 }
    //                      }

    //                      if($fundRes["message"] == "Customer does not have sufficient funds in their Rave Available balance"){
    //                         return response(['success' => false, 'message' => "Unable to fund card try again later"]);
    //                      }

    //                      return response(['success' => false, 'message' => $fundRes["message"]]);
    //                     }


    //                     //update card details
    //                     $getPayload = [
    //                         'card_id' => $card->card_id,
    //                         'public_key' => env('STROWALLET_PUBLIC_KEY'),
    //                         // 'mode' => 'sandbox'
    //                     ];
    //                     $res = getCardDetails($getPayload);
    //                     if($res["status"]){
    //                     //  $card->update([
    //                     //      "amount" => $res["data"]->amount,
    //                     //      "masked_pan" => $res["data"]->masked_pan,
    //                     //      "name_on_card" => $res["data"]->name_on_card,
    //                     //      "card_pan" => $res["data"]->card_pan,
    //                     //      "expiration" => $res["data"]->expiration,
    //                     //      "currency" => $res["data"]->currency,
    //                     //      "card_type" => $res["data"]->card_type,
    //                     //      "is_active" => $res["data"]->is_active == true || $res["data"]->is_active == 1 ? true : false,
    //                     //  ]); 
    //                     }

    //                     $user->transaction()->where(['id' => $Fundingtansaction->id])->update(['status' => 'success']);

    //                     (new FirebaseUserUpdate)->addUserNotification($user,
    //                     [
    //                         "message" => $description,
    //                         "type" => "transaction",
    //                         "read" => 0,
    //                         "data" => [
    //                             "service" => 'Virtual Card Funding',
    //                             "id" => $Fundingtansaction->id,
    //                             "reference_id" => $trx,
    //                             "status" => "success",
    //                             'date' => $date 
    //                         ]
    //                     ]
    //                     );

    //                     if (getSiteSettings("email_notify") == "Yes") {
    //                         try {
    //                             $this->sendMail($user->email, $user->profile->first_name, 'Virtual Card Funding', $description);
    //                         } catch (\Throwable $th) {
    //                             //throw $th;
    //                         }
    //                     } 

    //                     (new FirebaseUserUpdate)->updateUser($user);
    //                     return response()->json([
    //                         'success' => true,
    //                         'code' => 200,
    //                         'status' => 'successful',
    //                         "message" => "Card Funded Successfully",
    //                         'data' => [
    //                             'service' => "Virtual Card Funding",
    //                             'amount' => $request->amount,
    //                             'charge' => $charge,
    //                             'phone_number' => $request->phone_number,
    //                             'date' => $date,
    //                             'message' => $description,
    //                             'reference' => $trx,
    //                             'wallet_balance' => $final_balance,
    //                         ],
    //                         'card' =>  $user->virtualCard,
    //                         'user' => $user
    //                     ]);


    // }


    public function withdrawFromVirtualCard(Request $request)
    {
        try {
            $date = Carbon::now();
    
            $validatedData = Validator::make($request->all(), [
                'amount'  => 'required|numeric|min:0.01',
                'card_id' => 'required|integer',
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $validatedData->errors()->all())
                ]);
            }
    
            $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => "User not authenticated"
                ]);
            }
    
            // Transaction PIN check
            if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
            if (!$user->hasVerifyWallet) {
                return response()->json([
                    'success' => false,
                    'message' => "Kindly verify your account"
                ]);
            }
    
            $card = VirtualCardUSD::where('id', $request->card_id)
                ->where('user_id', $user->id)
                ->first();
    
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => "Card not found or unauthorized"
                ]);
            }
    
            if (getSiteSettings("enable_virtual_dollar_card") !== "Yes") {
                return response()->json([
                    'success' => false,
                    'message' => "Virtual card service is currently disabled"
                ]);
            }
    
            $withdrawAmount = floatval(str_replace(',', '', $request->amount));
            $sellingRate    = getSiteSettings("selling_dollar_rate");
            $walletAmount   = number_format($withdrawAmount * $sellingRate, 2, '.', '');
    
            // Fetch card balance
            $cardDetails = getCardDetails([
                'card_id'    => $card->card_id,
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
            ]);
    
            if (!$cardDetails['status']) {
                return response()->json([
                    'success' => false,
                    'message' => "Unable to fetch card details"
                ]);
            }
    
            $currentBalance = $cardDetails['data']->amount ?? 0;
    
            if ($currentBalance < $withdrawAmount || ($currentBalance - $withdrawAmount) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient card balance"
                ]);
            }
    
            // Withdraw from card
            $withdrawRes = withdrawFromCard(
                $card->card_id,
                $withdrawAmount,
                env('STROWALLET_PUBLIC_KEY')
            );
    
            if (!$withdrawRes['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $withdrawRes['message'] ?? "Withdrawal failed"
                ]);
            }
    
            // Credit wallet
            $walletId = 1;
            $balanceAction = userBalanceAction($user, $walletAmount, $walletId, "+");
    
            if (!$balanceAction) {
                \Log::critical("Wallet credit failed after card withdrawal for user {$user->id}");
                return response()->json([
                    'success' => false,
                    'message' => "Withdrawal completed but wallet credit failed. Contact support."
                ]);
            }
    
            $trx = strtoupper(Str::random(16));
            $description = "Withdrawal of {$withdrawAmount} USD from your Virtual Card";
    
            $transaction = $user->transaction()->create([
                'user_level_id'   => $user->user_level_id,
                'wallet_id'       => $walletId,
                'service'         => "System",
                'service_type'    => "Virtual Card",
                'description'     => $description,
                'amount'          => $walletAmount,
                'phone_number'    => $user->phone_number,
                'type'            => 1,
                'initial_balance' => $balanceAction['init_balance'],
                'final_balance'   => $balanceAction['final_balance'],
                'reference'       => $trx,
                'status'          => "success",
            ]);
    
            // Firebase notification
            (new FirebaseUserUpdate)->updateUser($user);
            (new FirebaseUserUpdate)->addUserNotification($user, [
                "message" => $description,
                "type"    => "transaction",
                "read"    => 0,
                "data"    => [
                    "reference_id" => $trx,
                    "status"       => "success",
                    "date"         => $date
                ]
            ]);
    
            // ✅ SEND EMAIL (SUCCESS ONLY)
            if (getSiteSettings("email_notify") === "Yes") {
                try {
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name ?? $user->name,
                        "Virtual Card Withdrawal Successful",
                        "You have successfully withdrawn {$withdrawAmount} USD from your virtual card. Reference: {$trx}"
                    );
                } catch (\Throwable $e) {
                    \Log::warning("Withdrawal email failed for user {$user->id}: " . $e->getMessage());
                }
            }
    
            return response()->json([
                'success' => true,
                'status'  => 'successful',
                'message' => "Card Fund Withdrawal Successful",
                'data'    => [
                    'amount'         => $withdrawAmount,
                    'reference'      => $trx,
                    'wallet_balance' => $balanceAction['final_balance'],
                    'date'           => $date,
                ]
            ]);
    
        } catch (\Throwable $e) {
            \Log::error("Virtual Card Withdrawal Error: " . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => "An unexpected error occurred. Please try again."
            ]);
        }
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
            
            if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
            
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

    public function changeCardPin(Request $request)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
        }
    
        $validator = Validator::make($request->all(), [
            'card_id'    => 'required',
            'old_pin'    => 'required|digits:4',
            'new_pin'    => 'required|digits:4|different:old_pin',
            'confirm_pin'=> 'required|same:new_pin',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ]);
        }
    
        $validated = $validator->validated();
    
        // ✅ Transaction PIN Check
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = VirtualCardNGN::where('id', $validated['card_id'])
            ->where('user_id', $user->id)
            ->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found.',
            ]);
        }
    
        if (!$card->card_id) {
            return response()->json([
                'success' => false,
                'message' => 'Card ID missing.',
            ]);
        }
    
        if (!Hash::check($validated['old_pin'], $card->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'Old PIN is incorrect.',
            ]);
        }
    
        $client = new \GuzzleHttp\Client();
    
        $query = http_build_query([
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
            'card_id'    => $card->card_id,
            'old_pin'    => $validated['old_pin'],
            'new_pin'    => $validated['new_pin'],
        ]);
    
        try {
    
            $response = $client->post("https://strowallet.com/api/naira_changepin/?$query", [
                'headers' => ['accept' => 'application/json'],
            ]);
    
            $result = json_decode($response->getBody(), true);
    
            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to change card PIN.',
                    'data'    => $result,
                ]);
            }
    
            // ✅ Save new PIN locally
            $card->pin = Hash::make($validated['new_pin']);
            $card->save();
    
            /*
            |--------------------------------------------------------------------------
            | SEND SUCCESS EMAIL (SAFE BLOCK)
            |--------------------------------------------------------------------------
            */
            if (getSiteSettings("email_notify") === "Yes") {
                try {
    
                    $message = "
    Your virtual card PIN was changed successfully.
    
    If you did not perform this action, please contact support immediately.
    
    Thank you for using Dupay.
                    ";
    
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name ?? $user->name ?? 'Customer',
                        'Virtual Card PIN Change Notification',
                        $message
                    );
    
                } catch (\Throwable $th) {
                    \Log::warning('Card PIN email failed', [
                        'user_id' => $user->id,
                        'error'   => $th->getMessage()
                    ]);
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Card PIN changed successfully.',
                'data'    => $result,
            ]);
    
        } catch (\GuzzleHttp\Exception\RequestException $e) {
    
            $errorBody = $e->getResponse()
                ? json_decode($e->getResponse()->getBody(), true)
                : null;
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to change card PIN.',
                'error'   => $errorBody ?? $e->getMessage(),
            ]);
        }
    }
    
    public function resetCardPin(Request $request)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'card_id'     => 'required',
            'new_pin'     => 'required|digits:4',
            'confirm_pin' => 'required|same:new_pin',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }
    
        $validated = $validator->validated();
    
        // ✅ Transaction PIN Check
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = VirtualCardNGN::where('id', $validated['card_id'])
            ->where('user_id', $user->id)
            ->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found or does not belong to the user.',
            ]);
        }
    
        if (!$card->card_id) {
            return response()->json([
                'success' => false,
                'message' => 'Card ID is missing on the record.',
            ]);
        }
    
        try {
    
            $client = new \GuzzleHttp\Client();
    
            $query = http_build_query([
                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                'card_id'    => $card->card_id,
                'new_pin'    => $validated['new_pin'],
            ]);
    
            $response = $client->put("https://strowallet.com/api/naira_resetPin/?$query", [
                'headers' => ['accept' => 'application/json'],
            ]);
    
            $result = json_decode($response->getBody(), true);
    
            if (!($result['success'] ?? false)) {
                return response()->json([
                    'success'  => false,
                    'message'  => $result['message'] ?? 'Failed to reset PIN.',
                    'response' => $result
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | SEND SUCCESS EMAIL (SAFE BLOCK)
            |--------------------------------------------------------------------------
            */
            if (getSiteSettings("email_notify") === "Yes") {
                try {
    
                    $message = "
    Your virtual card PIN has been reset successfully.
    
    If you did not perform this action, please contact support immediately.
    
    Thank you for using Dupay.
                    ";
    
                    $this->sendMail(
                        $user->email,
                        $user->profile->first_name ?? $user->name ?? 'Customer',
                        'Virtual Card PIN Reset Notification',
                        $message
                    );
    
                } catch (\Throwable $th) {
                    \Log::warning('Card PIN reset email failed', [
                        'user_id' => $user->id,
                        'error'   => $th->getMessage()
                    ]);
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Card PIN reset successfully.',
                'data'    => $result,
            ]);
    
        } catch (\GuzzleHttp\Exception\RequestException $e) {
    
            $errorBody = $e->getResponse()
                ? json_decode($e->getResponse()->getBody(), true)
                : null;
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset card PIN.',
                'error'   => $errorBody ?? $e->getMessage(),
            ]);
        }
    }
    
    public function changeCardStatus(Request $request)
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
    
        $validated = Validator::make($request->all(), [
            'card_id' => 'required',
        ]);
    
        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validated->errors(),
            ]);
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
    
        $card = VirtualCardNGN::where('id', $request->card_id)
            ->where('user_id', $user->id)
            ->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found.',
            ]);
        }
    
        if (!$card->card_id) {
            return response()->json([
                'success' => false,
                'message' => 'Missing card_id in record.',
            ]);
        }
    
        $newStatus = $card->status === 'active' ? 'inactive' : 'active';
    
        $client = new \GuzzleHttp\Client();
    
        $query = http_build_query([
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
            'card_id'    => $card->card_id,
            'status'     => $newStatus,
            // 'mode'       => 'live',
        ]);
    
        try {
            $response = $client->put("https://strowallet.com/api/naira_ChangeStatus/?$query", [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);
    
            $result = json_decode($response->getBody(), true);
    
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'API call failed.',
                    'response' => $result,
                ]);
            }
    
            // Update status in database
            $card->status = $newStatus;
            $card->save();
    
        
            
            
             return response([
                 'success' => true, 
                 'message' => "Card status changed to {$newStatus}.",
                 'card' =>  $card,
                  'user' => $user
            ]);

            
            
            
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorBody = $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
    
            return response()->json([
                'status' => false,
                'message' => 'Request failed.',
                'error' => $errorBody ?? $e->getMessage(),
            ]);
        }
    }
    
    public function get(){
        $cardPayload = [
            'card_id' => "431ed4c3-1885-44d9-b5ed-74f3d126f4cc",
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
            // 'mode' => 'sandbox'
        ];
        $cardData = getCardDetails($cardPayload);
        
        return $cardData;
    }
    
    public function enable2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        $validated = $validator->validated();
        
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
    
        $card = VirtualCardNGN::where('id', $validated['card_id'])->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found.',
            ], 404);
        }
    
        $response = enableSafeToken(env('STROWALLET_PUBLIC_KEY'), $card->card_id);
        return $response;
    }
    
    public function freezeUnfreeze(Request $request)
    {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
    
            $validator = Validator::make($request->all(), [
                'card_id' => 'required',
                'action'  => 'required|string|in:freeze,unfreeze',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }
    
            $validated = $validator->validated();
    
            // ✅ Transaction PIN Check
            $isSmartRequest = isset($_SERVER['HTTP_ISMART']) 
                && $_SERVER['HTTP_ISMART'] === "api" 
                && $request->fingerPrint == true;
    
            if (!$isSmartRequest) {
                if (!$request->filled('transaction_pin')) {
                    return response()->json([
                        'success' => false,
                        'message' => "Please Enter Transaction Pin"
                    ], 422);
                }
    
                if (!$user->transaction_pin) {
                    return response()->json([
                        'success' => false,
                        'message' => "Transaction Pin not set"
                    ], 422);
                }
    
                if (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Incorrect Transaction Pin"
                    ], 422);
                }
            }
    
            $card = VirtualCardUSD::where('id', $validated['card_id'])
                ->where('user_id', $user->id)
                ->first();
    
            if (!$card) {
                return response()->json([
                    'success' => false,
                    'message' => "Card not found or unauthorized"
                ], 404);
            }
            
            $status = $validated['action'] === 'freeze' ? 'frozen' : 'active';
            $nextAction = $validated['action'] === 'freeze' ? 'unfreeze' : 'freeze';
    
            $response = freezeOrUnfreezeCard($validated['action'], $card->card_id, env('STROWALLET_PUBLIC_KEY'));
    
            if ($response['success']) {
                $card->update([
                    'status' => $status,
                    'freeze_status' => $nextAction,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Card status updated successfully.",
                    'card' => $card,
                    'user' => $user
                ], 200);
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update card status.',
                'error'   => $response['message'] ?? null
            ], $response['status_code'] ?? 400);
    
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Return FRESH secure reveal URLs for the USD card. Called when the user
     * opens "Card Details". The app renders these URLs in a WebView to show the
     * real number/CVV — the raw digits never touch our server (PCI vault).
     */
    public function revealCard($currency)
    {
        $user = \Auth::user();

        if (strtoupper($currency) !== 'USD') {
            return response()->json(['success' => false, 'message' => 'Unsupported card type.'], 422);
        }

        $card = \App\Models\VirtualCardUSD::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$card || empty($card->card_id)) {
            return response()->json(['success' => false, 'message' => 'No active USD card found.'], 404);
        }

        $secrets = getNfcCardSecrets($card->card_id);

        if (empty($secrets['success']) || empty($secrets['card_number_url'])) {
            return response()->json([
                'success' => false,
                'message' => $secrets['message'] ?? 'Card details are not available yet. Please try again shortly.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Card reveal URLs generated.',
            'data'    => [
                'card_number_url' => $secrets['card_number_url'],
                'cvv_url'         => $secrets['cvv_url'],
                'last4'           => $secrets['last4'] ?? $card->last4 ?? null,
                'expiry'          => $secrets['expiry'] ?? $card->expiry ?? null,
            ],
        ]);
    }

}