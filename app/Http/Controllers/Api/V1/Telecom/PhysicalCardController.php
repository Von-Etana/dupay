<?php

namespace App\Http\Controllers\Api\V1\Telecom;

use App\Http\Controllers\Controller;
use App\Models\PhysicalCard;
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
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\AssignedCardCredential;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;



class PhysicalCardController extends Controller
{
    use MailTrait;




  public function __construct()
    { 
        $this->middleware('verifyUser');
        $this->middleware('auth:api'); 
    }

    public function index(){
        $user = Auth::user();
        if(!$user->physicalCard){
            return response(['success' => true, 'message' => "No  Card found", 'data' => $user->virtualCard]);
        }
        
        $physicalCard = PhysicalCard::where('user_id', $user->id)->first();

    
        (new FirebaseUserUpdate)->updateUser($user);
        
        return response(['success' => true, 'data' => $user->physicalCard]);
 
    }
    
    
    public function store(Request $request){
    
        $user = Auth::user();
        //debit user for card creation, admin should set the price to order an atm card  
        
        //maybe limit user to request more than 1 atm card 
        
        //call the merchant to create customer 
        
        //insert pending to database, pending when the user gets the card 
        
        
        
        
        $existingCard = PhysicalCard::where('user_id', $user->id)
        ->whereIn('status', ['pending', 'active'])
        ->first();

    if ($existingCard) {
        
        return response()->json([
            'success' => false,
            'message' => 'You already have a pending or active card request.',
        ], 400);
        
        
    }

    // ── Create the StroWallet customer up front ─────────────────────────────
    // Do this BEFORE charging so the card can actually be activated later
    // (activation requires strowallet_customer_id). Creating it at order time
    // also gives StroWallet time to approve the customer before the user
    // activates. If it fails we stop here and never charge the user.
    $ensured = $this->ensureStrowalletCustomer($user);
    if (!$ensured['status']) {
        return response()->json([
            'success'        => false,
            'message'        => $ensured['message'] ?? 'Unable to set up your card profile.',
            'missing_fields' => $ensured['missing_fields'] ?? null,
        ], 400);
    }

    // ── Debit user for the physical card request ────────────────────────────
    // The fee is admin-configurable via General Settings (column_name:
    // "physical_card_creation_fee"). Falls back to 0 if not set.
    $select_wallet_id = "1"; // main NGN wallet
    $fee = (float) getSiteSettings("physical_card_creation_fee");

    if ($fee > 0) {
        // Confirm the user can afford it before doing anything else.
        if (!checkUserWallet($user, $fee, $select_wallet_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance to request a physical card. Fee is ' . number_format($fee, 2),
            ], 400);
        }
    }

    // Create the card request first, then debit inside a transaction so we
    // never charge a user without a card record (or vice-versa).
    try {
        \DB::beginTransaction();

        $card = PhysicalCard::create([
            'user_id' => $user->id,
            'type'    => 'physical',
            'status'  => 'pending',
        ]);

        $init_balance  = $user->balance;
        $final_balance = $user->balance;

        if ($fee > 0) {
            $balanceAction = userBalanceAction($user, $fee, $select_wallet_id, "-");
            if (!$balanceAction["success"]) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet balance error. Please try again.',
                ], 400);
            }
            $init_balance  = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];

            // Record the debit as a transaction so it shows in history.
            $Trx = strtoupper(\Illuminate\Support\Str::random(16));
            $user->transaction()->create([
                'wallet_id'       => $select_wallet_id,
                'user_level_id'   => $user->user_level_id,
                'service'         => 'Physical Card',
                'service_type'    => 'Physical Card Request',
                'description'     => 'Physical ATM card request fee',
                'amount'          => $fee,
                'charge'          => 0,
                'type'            => '0', // debit
                'initial_balance' => $init_balance,
                'final_balance'   => $final_balance,
                'reference'       => $Trx,
                'status'          => 'success',
            ]);
        }

        \DB::commit();
    } catch (\Throwable $e) {
        \DB::rollBack();
        \Log::error('Physical card request failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Could not complete your card request. Please try again.',
        ], 500);
    }

    $card->refresh();

    return response()->json([
        'success' => true,
        'message' => 'Card request submitted successfully. Awaiting approval or processing.',
        'data' => $card
    ]);
        
        
         
        
        
        
        

        
    }
    
    // public function createCustomer()
    // {
    //     $user = Auth::user();
    
    //     if (!$user->hasVerifyWallet) {
    //         return response([
    //             'success' => false,
    //             'message' => "Kindly verify your account"
    //         ]);
    //     }
    
    //     if (getSetting("enable_atm_card_creation") != "Yes") {
    //         return response([
    //             'success' => false,
    //             'message' => "Sorry ATM card creation is disabled at the moment, try again later"
    //         ]);
    //     }
        
    //     if ($user->strowallet_customer_id) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'You already applied for an atm card',
    //             'customer_id' => $user->strowallet_customer_id
    //         ], 200);
    //     }
    
    //     if (!$user->walletVerification || !$user->profile) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User profile or wallet verification missing.'
    //         ]);
    //     }
    
    //     $profile = $user->profile;
    //     $wallet = $user->walletVerification;
    
    //     $nin = $wallet->bvn;
    //     $dob = $wallet->date_of_birth;
    //     $line1 = $wallet->address;
    //     $city = $wallet->city;
    //     $state = $wallet->state;
    //     $firstname = $profile->first_name;
    //     $lastname = $profile->last_name;
    
    //     $missingFields = [];

    //     if (empty($nin)) {
    //         $missingFields[] = 'nin';
    //     }
        
    //     if (empty($dob)) {
    //         $missingFields[] = 'dob';
    //     }
        
    //     if (empty($line1)) {
    //         $missingFields[] = 'line1';
    //     }
        
    //     if (empty($city)) {
    //         $missingFields[] = 'city';
    //     }
        
    //     if (empty($state)) {
    //         $missingFields[] = 'state';
    //     }
        
    //     if (empty($firstname)) {
    //         $missingFields[] = 'firstname';
    //     }
        
    //     if (empty($lastname)) {
    //         $missingFields[] = 'lastname';
    //     }
        
    //     if (!empty($missingFields)) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Missing required user data.',
    //             'missing_fields' => $missingFields
    //         ]);
    //     }
        
    //     try {
    //         $parsedDob = Carbon::createFromFormat('d-M-Y', ucwords(strtolower($dob)))->format('m/d/Y');
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid date format for date of birth. Use format like: 11-nov-1994',
    //             'raw_dob' => $dob
    //         ]);
    //     }
    
    //     $postData = [
    //         'public_key' => env('STROWALLET_PUBLIC_KEY'),
    //         'firstname'  => $firstname,
    //         'lastname'   => $lastname,
    //         'email'      => $user->email,
    //         'phone'      => '234' . ltrim($user->phone_number, '0'),
    //         'nin'        => $nin,
    //         'dob'        => $parsedDob,
    //         'name'       => $firstname.''.$lastname,
    //         'line1'      => $line1,
    //         'city'       => $city,
    //         'state'      => $state,
    //         // 'mode'       => 'live',
    //     ];
    
    //     try {
    //         $client = new Client();
    
    //         $response = $client->post('https://strowallet.com/api/naira_carduser/', [
    //             'headers' => [
    //                 'Accept' => 'application/json',
    //             ],
    //             'form_params' => $postData
    //         ]);
    
    //         $data = json_decode($response->getBody(), true);
    //         $customerId = $data['data']['customer_id'] ?? null;
            
    //         if ($customerId) {
    //             $user->strowallet_customer_id = $customerId;
    //             $user->save();
                
    //             PhysicalCard::create([
    //                 'user_id' => $user->id,
    //                 'type' => 'physical',
    //                 'status' => 'pending',
    //             ]);
            
    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Card request submitted successfully. Awaiting approval or processing.',
    //                 'data' => $data['data']
    //             ]);
    //         }
            
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Customer ID not returned from StroWallet.',
    //             'full_response' => $data
    //         ]);
    
    //     } catch (RequestException $e) {
    //         $errorBody = $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
    
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Customer creation failed.',
    //             'error' => $errorBody ?? $e->getMessage(),
    //             'date' => $parsedDob
    //         ]);
    //     }
    // }
    
    /**
     * Ensure the user has a StroWallet naira card customer id.
     *
     * Ordering a card only creates a local `physical_cards` row — it does NOT
     * create the StroWallet customer, and activation (createCard) hard-requires
     * `users.strowallet_customer_id`. This helper creates that customer from the
     * user's approved wallet KYC + profile and saves the id (createNairaCardUser
     * persists it). It is idempotent: if the id already exists it returns early.
     *
     * Returns ['status' => bool, 'message' => ?string, 'missing_fields' => ?array].
     */
    private function ensureStrowalletCustomer($user): array
    {
        // Already set up — nothing to do.
        if ($user->strowallet_customer_id) {
            return ['status' => true];
        }

        // Needs approved wallet KYC to pull NIN / DOB / address from.
        if (!$user->walletVerification) {
            return [
                'status'  => false,
                'message' => 'Account verification is required to set up your card. Please complete your KYC and try again.',
            ];
        }

        $profile = $user->profile;
        $wallet  = $user->walletVerification;

        $nin       = $wallet->bvn;
        $dob       = $wallet->date_of_birth;
        $line1     = $wallet->address;
        $city      = $wallet->city;
        $state     = $wallet->state;
        $firstname = $profile->first_name ?? null;
        $lastname  = $profile->last_name ?? null;

        $missingFields = [];
        foreach ([
            'nin' => $nin, 'dob' => $dob, 'line1' => $line1,
            'city' => $city, 'state' => $state,
            'firstname' => $firstname, 'lastname' => $lastname,
        ] as $key => $value) {
            if (empty($value)) $missingFields[] = $key;
        }

        if (!empty($missingFields)) {
            return [
                'status'         => false,
                'message'        => 'Missing required KYC data to set up your card.',
                'missing_fields' => $missingFields,
            ];
        }

        try {
            $parsedDob = Carbon::createFromFormat('Y-m-d', $dob)->format('Y/m/d');
        } catch (\Exception $e) {
            return [
                'status'  => false,
                'message' => 'Invalid date of birth on your KYC record. Please contact support.',
            ];
        }

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

        // createNairaCardUser() saves strowallet_customer_id on success.
        $response = createNairaCardUser($data, $user);

        if (empty($response['status'])) {
            return [
                'status'  => false,
                'message' => $response['message']
                    ?? 'We could not set up your card profile with our card partner. Please confirm your KYC details and try again.',
            ];
        }

        // Belt-and-braces: confirm the id actually landed before proceeding.
        $user->refresh();
        if (!$user->strowallet_customer_id) {
            return [
                'status'  => false,
                'message' => 'We could not set up your card profile with our card partner. Please confirm your KYC details and try again.',
            ];
        }

        return ['status' => true];
    }

    public function createCustomer(Request $request)
    {
        $request->validate([
            'address'  => 'required|string',
            'state'     => 'required|string',
            'lga'       => 'required|string',
            'landmark'  => 'required|string',
            'phone_number' => 'required|string',
        ]);
        
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
    
        if (!$user->walletVerification) {
            return response()->json([
                'status' => false,
                'message' => 'Account verification is required to proceed. Please verify your account to continue.',
            ]);
        }
    
        // if (!$user->hasVerifyWallet) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Kindly verify your account"
        //     ]);
        // }
    
        // if (getSiteSettings("enable_atm_card_creation") != "Yes") {
        //     return response()->json([
        //         'success' => false,
        //         'message' => "Sorry ATM card creation is disabled at the moment, try again later"
        //     ]);
        // }
        
        if (getSiteSettings("enable_physical_card_creation_fee") === "Yes") {
            $card_creation_fee = (float) getSiteSettings("physical_card_creation_fee");
        
            if ($card_creation_fee > $user->balance) {
                return response()->json([
                    'success' => false,
                    'message' => "You do not have sufficient funds to request a physical ATM card. A minimum balance of ₦" . number_format($card_creation_fee, 2) . " is required.",
                ]);
            }
        
        }
    
        $profile = $user->profile;
        $wallet = $user->walletVerification;
    
        if (!$user->strowallet_customer_id) {
    
            $nin = $wallet->bvn;
            $dob = $wallet->date_of_birth;
            $line1 = $wallet->address;
            $city = $wallet->city;
            $state = $wallet->state;
            $firstname = $profile->first_name;
            $lastname = $profile->last_name;
    
            $missingFields = [];
    
            foreach ([
                'nin' => $nin, 'dob' => $dob, 'line1' => $line1,
                'city' => $city, 'state' => $state,
                'firstname' => $firstname, 'lastname' => $lastname
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
    
            try {
                // $parsedDob = Carbon::createFromFormat('d-M-Y', ucwords(strtolower($dob)))->format('m/d/Y');
                $parsedDob = Carbon::createFromFormat('Y-m-d', $dob)->format('Y/m/d');
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid date format for date of birth. Use format like: 11-nov-1994',
                    'raw_dob' => $dob
                ]);
            }
    
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
    
            $response = createNairaCardUser($data, $user);
    
            if (!$response['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $response['message'] ?? 'Failed to create Naira card user.'
                ]);
            }
        }
    
        // Safety net: never create a card row / charge the fee unless the user
        // now actually has a StroWallet customer id. Without it the card can
        // never be activated (activation requires strowallet_customer_id).
        $user->refresh();
        if (!$user->strowallet_customer_id) {
            return response()->json([
                'status' => false,
                'message' => 'We could not set up your card profile with our card partner. Please confirm your KYC details and try again.',
            ]);
        }
    
        $checkPending = PhysicalCard::where('user_id', $user->id)->where('setup_step', '<=', 1)->first();
        if ($checkPending) {
            return response()->json([
                'status' => false,
                'message' => 'You already applied for an ATM card'
            ]);
        }
        
        $shippingDetails = [
            'address' => $request->address,
            'lga'    => $request->lga,
            'state'   => $request->state,
            'phone_number'  => $request->phone_number,
            'landmark'  => $request->landmark,
        ];
    
        $physicalCard = PhysicalCard::create([
            'user_id' => $user->id,
            'type'    => 'physical',
            'status'  => 'pending',
            'shipping_details'  => json_encode($shippingDetails),
        ]);
        
        // Deduct card creation fee from user balance
        $balanceAction = userBalanceAction($user, $card_creation_fee, '1', "-");
        if (!$balanceAction || !isset($balanceAction["init_balance"], $balanceAction["final_balance"])) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process card creation fee deduction. Please try again later.',
            ]);
        }
    
        if (getSiteSettings("enable_physical_card_creation_fee") === "Yes") {
            $init_balance  = $balanceAction["init_balance"];
            $final_balance = $balanceAction["final_balance"];
            $realPrice     = $card_creation_fee;
        
            $description = "Physical ATM card creation fee";
            $Trx = strtoupper(Str::random(16));
        
            $transactionData = [
                'wallet_id'         => 1,
                'user_level_id'     => $user->user_level_id,
                'service'           => 'ATM Card Creation',
                'api_connection_id' => null,
                'service_type_id'   => null,
                'service_plan_id'   => null,
                'service_type'      => 'Card Service',
                'description'       => $description,
                'amount'            => $card_creation_fee,
                'real_price'        => $realPrice,
                'charge'            => 0,
                'phone_number'      => $user->phone_number,
                'type'              => '0',
                'initial_balance'   => $init_balance,
                'final_balance'     => $final_balance,
                'reference'         => $Trx,
                'status'            => 'success',
            ];
        
            $transaction = $user->transaction()->create($transactionData);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Card request submitted successfully. Awaiting approval or processing.',
            'data' => $response['data'] ?? null,
            'physical_card' => $physicalCard,
        ]);
    }
    
    public function createCard(Request $request)
    {
        $user = auth()->user();
        
        
        
        
    
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }
        
        
        
        
    
        // The user may have ordered a card without ever getting a StroWallet
        // customer id (ordering doesn't create one). Create it now from their
        // KYC if it's missing, so activation can proceed instead of dead-ending
        // on "You must Order for card…". Idempotent when the id already exists.
        $ensured = $this->ensureStrowalletCustomer($user);
        if (!$ensured['status']) {
            return response()->json([
                'success'        => false,
                'status'         => false,
                'message'        => $ensured['message'] ?? 'Unable to set up your card profile.',
                'missing_fields' => $ensured['missing_fields'] ?? null,
            ]);
        }
        $user->refresh();
    
        // $existingCard = PhysicalCard::where('user_id', $user->id)->latest()->first();
        // if ($existingCard && $existingCard->expiry_month && $existingCard->expiry_year) {
        //     $expiry = Carbon::createFromDate($existingCard->expiry_year, $existingCard->expiry_month, 1)->endOfMonth();
        //     if ($expiry->isFuture()) {
        //         return response()->json([
        //             'status' => true,
        //             'message' => 'Card already exists and is still valid.',
        //             'card' => $existingCard,
        //         ]);
        //     }
        // }
    
        $data = $request->all();
        
        $validator = Validator::make($data, [
            'card_pan'    => 'required|string|size:6',
            'cvv'         => 'required|string|size:3',
            'card_pin'    => 'required|digits:4', 
            'physical_card_id' => 'required|exists:physical_cards,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ]);
        }
        
        $validatedData = $validator->validated();
        
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        // ── Check 1: Card credentials must exist and belong to THIS user ────────
        $assigned = AssignedCardCredential::where('user_id', $user->id)
            ->where('card_pan_last6', $validatedData['card_pan'])
            ->where('cvv', $validatedData['cvv'])
            ->first();
        
        if (!$assigned) {
            // Card details exist but were assigned to a DIFFERENT user
            $takenByOther = AssignedCardCredential::where('card_pan_last6', $validatedData['card_pan'])
                ->where('cvv', $validatedData['cvv'])
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($takenByOther) {
                return response()->json([
                    'status' => false,
                    'message' => 'This card has already been activated by another user.',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'The card details provided are incorrect. Please verify and try again.',
            ]);
        }

        // ── Check 2: Card credentials already used (by this same user) ───────────
        if ($assigned->used) {
            return response()->json([
                'status' => false,
                'message' => 'This card has already been activated on your account.',
            ]);
        }

        // ── Check 3: Card PAN already linked to a DIFFERENT user's PhysicalCard ──
        // Catches edge cases where Strowallet accepted the card for another user
        // even if AssignedCardCredential wasn't flagged (e.g. manual admin assignment).
        $panUsedByOther = PhysicalCard::where('card_pan', $validatedData['card_pan'])
            ->where('user_id', '!=', $user->id)
            ->whereNotNull('card_id')
            ->exists();

        if ($panUsedByOther) {
            return response()->json([
                'status' => false,
                'message' => 'This card has already been activated by another user.',
            ]);
        }

        // ── Check 4: Global duplicate guard BEFORE hitting StroWallet ───────────
        // If this exact card PAN already exists in our database with a card_id,
        // it has already been created on StroWallet. Do NOT send it again —
        // this prevents duplicate card creation on the provider side.
        $alreadyCreated = PhysicalCard::where('card_pan', $assigned->card_pan)
            ->whereNotNull('card_id')
            ->first();

        if ($alreadyCreated) {
            return response()->json([
                'status' => false,
                'message' => 'This card already exists and has been activated. Duplicate activation is not allowed.',
            ]);
        }
    
      $postData = [
    'public_key' => env('STROWALLET_PUBLIC_KEY'),
    'customerId' => $user->strowallet_customer_id,
    'type'       => 'physical',
    'number'     => $assigned->card_pan,
    'brand'      => $assigned->brand,
] + stroModeParam();
    
        try {
            $client = new Client();
    
            // 1. Create the card
            $response = $client->post('https://strowallet.com/api/naira_createcard/', [
                'headers' => ['Accept' => 'application/json'],
                'form_params' => $postData,
            ]);
    
            $body = json_decode($response->getBody(), true);
            $cardData = $body['data'] ?? [];
    
            if (!isset($cardData['card_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Card created but no card ID returned.',
                    'response' => $body
                ]);
            }
            
            // 1.a. Save card to DB
            $channels = $cardData['spendingControls']['channels'] ?? [];
            $limit = $cardData['spendingControls']['spendingLimits'][0] ?? [];
    
            $card = PhysicalCard::where('user_id', $user->id)->where('id', $request->physical_card_id)->first();

            if ($card) {
                $card->update([
                    'customer_id'              => $cardData['customer_id'] ?? $card->customer_id,
                    'card_id'                  => $cardData['card_id'] ?? $card->card_id,
                    'currency'                 => $cardData['currency'] ?? $card->currency ?? 'NGN',
                    'type'                     => $cardData['type'] ?? $card->type ?? 'physical',
                    'brand'                    => $cardData['brand'] ?? $card->brand ?? 'Verve',
                    'masked_pan'               => $cardData['maskedPan'] ?? $card->masked_pan,
                    'expiry_month'             => $cardData['expiryMonth'] ?? $card->expiry_month,
                    'expiry_year'              => $cardData['expiryYear'] ?? $card->expiry_year,
                    'status'                   => $cardData['status'] ?? $card->status,
            
                    'atm'                      => $channels['atm'] ?? $card->atm,
                    'pos'                      => $channels['pos'] ?? $card->pos,
                    'web'                      => $channels['web'] ?? $card->web,
                    'mobile'                   => $channels['mobile'] ?? $card->mobile,
            
                    'spending_limit_amount'    => $limit['amount'] ?? $card->spending_limit_amount,
                    'spending_limit_interval'  => $limit['interval'] ?? $card->spending_limit_interval,
                    'pin'                      => Hash::make($validatedData['card_pin']),
                    'card_pan'                 => $validatedData['card_pan'],
                    'setup_step'               => 2,
                    'cvv'                      => $request->cvv,
                    
                ]);
            }
            
            $assigned->used = true;
            $assigned->save();
    
            // 2. Reset PIN
            // Mode must match the card-creation call above (which uses
            // stroModeParam()). It was hardcoded to 'sandbox', so on a LIVE
            // setup the card was created live but the PIN reset was sent as
            // sandbox — a mismatch that fails with "Card created but failed to
            // set PIN." Use the same env-driven switch here.
            $pinResponse = $client->put('https://strowallet.com/api/naira_resetPin/', [
                'headers' => ['Accept' => 'application/json'],
                'form_params' => [
                    'public_key' => env('STROWALLET_PUBLIC_KEY'),
                    'card_id'    => $cardData['card_id'],
                    'new_pin'    => $validatedData['card_pin'],
                ] + stroModeParam(),
            ]);

            $pinResult = json_decode($pinResponse->getBody(), true);

            if (empty($pinResult['success'])) {
                return response()->json([
                    'success' => false,
                    'status'  => false,
                    'message' => 'Card created but failed to set PIN.',
                    'error'   => $pinResult
                ]);
            }

            // NOTE: the app reads `success` (see _handleSimpleResponse), not
            // `status`, so a `status`-only success was being treated as a
            // failure. Return both.
            return response()->json([
                'success' => true,
                'status'  => true,
                'message' => 'Card created and PIN set successfully.',
                'data'    => $card,
                'card'    => $card,
            ]);
    
        } catch (RequestException $e) {
            $errorBody = $e->getResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
            
                 Log::info('API Response', [
                        'status_code' => '400',
                        'response' =>  $errorBody ?? $e->getMessage()
                    ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Operation failed.',
                'error' => $errorBody ?? $e->getMessage(),
            ]);
        }
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
            'physical_card_id' => 'required|exists:physical_cards,id',
            'old_pin'          => 'required|digits:4',
            'new_pin'          => 'required|digits:4|different:old_pin',
            'confirm_pin'      => 'required|same:new_pin',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ]);
        }
    
        $validated = $validator->validated();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = PhysicalCard::where('id', $validated['physical_card_id'])
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
    
            // Save new PIN locally
            $card->pin = Hash::make($validated['new_pin']);
            $card->save();
    
            /*
            |--------------------------------------------------------------------------
            | Send Email Notification (SAFE BLOCK)
            |--------------------------------------------------------------------------
            */
            try {
    
                $message = "
                    Your debit card PIN was changed successfully.
    
                    If you did not perform this action, please contact support immediately to secure your account.
    
                    Thank you for using Dupay.
                ";
    
                $this->sendMail(
                    $user->email,
                    $user->profile->first_name ?? 'Customer',
                    'Dupay Card PIN Change Notification',
                    $message
                );
    
            } catch (\Throwable $th) {
                \Log::error('PIN change email failed', [
                    'user_id' => $user->id,
                    'error'   => $th->getMessage()
                ]);
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
            'physical_card_id' => 'required|exists:physical_cards,id',
            'new_pin'          => 'required|digits:4',
            'confirm_pin'      => 'required|same:new_pin',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }
    
        $validated = $validator->validated();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = PhysicalCard::where('id', $validated['physical_card_id'])
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
    
            if (empty($result['success'])) {
                return response()->json([
                    'success'  => false,
                    'message'  => $result['message'] ?? 'Failed to reset PIN.',
                    'response' => $result
                ]);
            }
    
            // Save hashed PIN locally
            $card->pin = Hash::make($validated['new_pin']);
            $card->save();
    
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    
        $validated = Validator::make($request->all(), [
            'physical_card_id' => 'required|exists:physical_cards,id',
        ]);
    
        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validated->errors(),
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = PhysicalCard::where('id', $request->physical_card_id)
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
    
        // Toggle status
        $newStatus = $card->status === 'active' ? 'inactive' : 'active';
    
        $client = new \GuzzleHttp\Client();
    
        $query = http_build_query([
            'public_key' => env('STROWALLET_PUBLIC_KEY'),
            'card_id'    => $card->card_id,
            'status'     => $newStatus,
        ]);
    
        try {
    
            $response = $client->put("https://strowallet.com/api/naira_ChangeStatus/?$query", [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);
    
            $result = json_decode($response->getBody(), true);
    
            if (empty($result['success'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'API call failed.',
                    'response' => $result,
                ]);
            }
    
            // Update status locally
            $card->status = $newStatus;
            $card->save();
    
            return response()->json([
                'success' => true,
                'message' => "Card status changed to {$newStatus}.",
                'card'    => $card,
                'user'    => $user
            ]);
    
        } catch (\GuzzleHttp\Exception\RequestException $e) {
    
            $errorBody = $e->getResponse()
                ? json_decode($e->getResponse()->getBody(), true)
                : null;
    
            return response()->json([
                'success' => false,
                'message' => 'Request failed.',
                'error'   => $errorBody ?? $e->getMessage(),
            ]);
        }
    }
    
    public function cardToggleChannels(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|exists:physical_cards,card_id',
            'mobile'  => 'required',
            'web'     => 'required',
            'atm'     => 'required',
            'pos'     => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
    
        $user = Auth::user();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $validated = $validator->validated();
    
        $card = PhysicalCard::where('card_id', $validated['card_id'])->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found.',
            ], 404);
        }
    
        try {
    
            $card->mobile = $validated['mobile'] ? 1 : 0;
            $card->web    = $validated['web']    ? 1 : 0;
            $card->atm    = $validated['atm']    ? 1 : 0;
            $card->pos    = $validated['pos']    ? 1 : 0;
            $card->save();
    
            /*
            |--------------------------------------------------------------------------
            | Send Email Notification
            |--------------------------------------------------------------------------
            */
            try {
    
                $message = '
                <p>Your debit card channel settings have been successfully updated.</p>
                <p><strong>Current status:</strong></p>
                <ul>
                    <li>Mobile: '.($card->mobile ? 'Enabled' : 'Disabled').'</li>
                    <li>Web: '.($card->web ? 'Enabled' : 'Disabled').'</li>
                    <li>ATM: '.($card->atm ? 'Enabled' : 'Disabled').'</li>
                    <li>POS: '.($card->pos ? 'Enabled' : 'Disabled').'</li>
                </ul>
                <p>
                If you did not authorize this change, please contact our support team immediately.
                </p>
                ';
    
                $this->sendMail(
                    $user->email,
                    $user->profile->first_name ?? 'Customer',
                    'Dupay Debit Card Channel Update',
                    $message
                );
    
            } catch (\Throwable $th) {
                \Log::error('Mail sending failed', [
                    'error' => $th->getMessage()
                ]);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Card channels updated successfully.',
                'data' => [
                    'card_id' => $card->card_id,
                    'mobile'  => $card->mobile,
                    'web'     => $card->web,
                    'atm'     => $card->atm,
                    'pos'     => $card->pos,
                ],
            ]);
    
        } catch (\Exception $e) {
    
            \Log::error('Failed to update card channels', [
                'card_id' => $validated['card_id'],
                'error'   => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update card channels.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
    public function enable2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'required|exists:physical_cards,card_id',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
    
        $validated = $validator->validated();
    
        $user = Auth::user();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = PhysicalCard::where('card_id', $validated['card_id'])->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found.',
            ], 404);
        }
    
        try {
    
            $apiResponse = enableSafeToken(env('STROWALLET_PUBLIC_KEY'), $card->card_id);
    
            /*
            |--------------------------------------------------------------------------
            | Normalize external API response
            |--------------------------------------------------------------------------
            */
            if (is_array($apiResponse) && isset($apiResponse['status'])) {
                $apiResponse['success'] = (bool) $apiResponse['status'];
                unset($apiResponse['status']);
            }
    
            // If helper already returns JSON response
            if ($apiResponse instanceof \Illuminate\Http\JsonResponse) {
                $data = $apiResponse->getData(true);
    
                if (isset($data['status'])) {
                    $data['success'] = (bool) $data['status'];
                    unset($data['status']);
                }
    
                return response()->json($data);
            }
    
            return response()->json([
                'success' => true,
                'message' => '2FA enabled successfully.',
                'data'    => $apiResponse
            ]);
    
        } catch (\Throwable $e) {
    
            \Log::error('Enable 2FA failed', [
                'card_id' => $validated['card_id'],
                'error'   => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to enable 2FA.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function reportCard(Request $request, $cardId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|in:lost,stolen',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
    
        $user = Auth::user();
    
        /*
        |--------------------------------------------------------------------------
        | Transaction Pin Check
        |--------------------------------------------------------------------------
        */
        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "api" || request("fingerPrint") != true) {
            if (!$request->transaction_pin) {
                return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
            } elseif (!$user->transaction_pin) {
                return response(['success' => false, 'message' => "Transaction Pin not set"]);
            } elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
                return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
            }
        }
    
        $card = PhysicalCard::where('user_id', $user->id)
            ->where('id', $cardId)
            ->first();
    
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found or does not belong to this user.',
            ], 404);
        }
    
        if ($card->blocked) {
            return response()->json([
                'success' => false,
                'message' => 'This card has already been reported or blocked.',
            ], 400);
        }
    
        DB::beginTransaction();
    
        try {
    
            $providerResponse = updateVirtualCardStatus($card->card_id, 'inactive');
    
            if (
                empty($providerResponse) ||
                !isset($providerResponse['success']) ||
                !$providerResponse['success']
            ) {
                DB::rollBack();
    
                return response()->json([
                    'success' => false,
                    'message' => $providerResponse['message']
                        ?? 'Failed to deactivate card on provider side.',
                ], 500);
            }
    
            $card->update([
                'blocked'      => true,
                'block_reason' => $request->reason ?? 'lost',
                'blocked_at'   => now(),
            ]);
    
            DB::commit();
    
            $message = "We are writing to confirm that your physical card has been successfully blocked, as per your request. Should you have any concerns or if you did not initiate this action, please contact our customer support team immediately for assistance.";
    
            $this->sendMail(
                $user->email,
                $user->profile->first_name,
                'Your Card Has Been Successfully Blocked',
                $message
            );
    
            return response()->json([
                'success' => true,
                'message' => 'Your card has been reported as ' . ($request->reason ?? 'lost') . ' and blocked successfully.',
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            Log::error('Error reporting card', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'card_id' => $cardId,
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again later.',
            ], 500);
        }
    }
    
}