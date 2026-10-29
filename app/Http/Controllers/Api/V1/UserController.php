<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Telecom\Airtime;
use App\Models\Telecom\AirtimeToCash;
use App\Models\Telecom\AirtimeType;
use App\Models\Telecom\BankAccount;
use App\Models\Telecom\Bill;
use App\Models\Telecom\Cable;
use App\Models\Telecom\Data;
use App\Models\Telecom\Exam;
use App\Models\Telecom\Network;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;
use App\TraitsFolder\MailTrait;
use iSmartMonnify;
use iSmartPaystack;
use iSmartFlutterWave;
use App\Models\Telecom\VirtualAccount;
use App\Models\ErrorLog;

class UserController extends Controller
{

    use MailTrait;

    public function __construct()
    {
        $this->middleware('verifyUser')->except('logoutCurrentToken');
        $this->middleware('auth:api');
    }

    public function logoutCurrentToken(Request $request)
    {
        $token = $request->user('api')->token();
        if ($token === null || !$token->revoke()) {
            return response()->json(['success' => false, 'message' => 'Could not revoke token'], 503);
        }

        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    /**
     * POST /update-fcm-token
     *
     * Keeps the device's push token current for the authenticated user.
     *
     * Previously the token only ever reached us inside the login and register
     * payloads. That is too early on iOS: Firebase cannot mint an FCM token
     * until APNs has returned a device token, which takes a few seconds, so a
     * user who taps Login immediately sends us nothing and that device then
     * receives no notifications at all until their next sign-in. The same gap
     * loses tokens that rotate after a reinstall, restore or app-data clear.
     */
    public function updateFcmToken(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'fcmToken' => 'required|string|max:512',
        ]);

        if ($validated->fails()) {
            return response([
                'success' => false,
                'message' => implode(' ', $validated->errors()->all()),
            ]);
        }

        $user = Auth::user();
        if (!$user) {
            return response(['success' => false, 'message' => 'Login Expire Kindly Relogin']);
        }

        // No-op when it hasn't actually changed, so a token refresh on every
        // cold start doesn't write to the users table needlessly.
        if ($user->fcmToken !== $request->fcmToken) {
            $user->fcmToken = $request->fcmToken;
            $user->save();
        }

        return response(['success' => true, 'message' => 'Token updated']);
    }

    public function createTransactionPin(Request $request)
    {
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'pin' => 'required|min:4|max:4',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();

        if ($user->transaction_pin != "" || $user->transaction_pin != null) {
            return response(['success' => false, 'message' => "You have already created a transaction PIN."]);
        }

        $user->transaction_pin = Hash::make($request->pin);
        $user->save();
        $user = User::whereId(auth()->user()->id)->with('profile')->firstOrFail();

        return response(['success' => true, "user" => $user, "data" => [
            'message' => "Transaction Pin Created",
            'wallet_balance' => $user->balance,
        ]]);
    }


    public function changeTransactionPin(Request $request)
    {
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'old_pin' => 'required|min:4|max:4',
            'pin' => 'required|min:4|max:4',

        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();

        if (!Hash::check($request->old_pin, $user->transaction_pin)) {
            return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
        }

        $user->transaction_pin = Hash::make($request->pin);
        $user->save();

        return response(['success' => true, "user" => $user,  "data" => [
            'message' => "Transaction Pin has been updated",
            'wallet_balance' => $user->balance,
        ]]);
    }



    public function updatePassword(Request $request)
   {
       $data = $request->all();
        $validatedData  = Validator::make($data, [
            'current_password' => 'required',
            'password' => 'required|min:5|confirmed',
        ]);

       if ($validatedData->fails()) {
        $error = "";
        foreach ($validatedData->errors()->all() as $key => $value) {
            $error .= $value;
        }
        return response(['success' => false, 'message' => $error]);
    }

       try {
          $user = Auth::user();
           $c_password =  $user->password;
           if(Hash::check($request->current_password, $c_password)){
               $password = Hash::make($request->password);
               $user->password = $password;
               $user->save();
               return response(['success' => true, 'message' => 'Password Changes Successfully.', "user"=> $user], 200);
           }else{
            return response(['success' => false, 'message' => 'Current Password does Not Match.'], 200);
           }

       } catch (\PDOException $e) {
           return response(['success' => false, 'message' => $e->getMessage()], 200);
    }

   }


public function deleteAccount(Request $request)
    {
        $data = $request->all();
        $validatedData = Validator::make($data, [
            'password' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();

        // 1. Confirm identity
        if (!Hash::check($request->password, $user->password)) {
            return response(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        }

        // 2. Protect the user's money (remove this block to allow deletion regardless of balance)
        if ((float) $user->balance > 0) {
            return response([
                'success' => false,
                'message' => 'Your wallet still has a balance of ' . $user->balance .
                             '. Please withdraw your funds before deleting your account.',
            ]);
        }

        try {
            DB::beginTransaction();

            // 3. Log out everywhere
            $user->tokens()->update(['revoked' => true]);

            // 4. Disable the account. KEEP email, phone, KYC and transactions intact —
            //    retained (and traceable) for the mandated financial-records period.
            //    Email/phone stay locked, so this person cannot re-register with them.
            $user->ban_status      = '1';            // blocks any future login
            $user->transaction_pin = null;
            $user->fcmToken        = null;
            $user->password        = Hash::make(Str::random(40)); // kills any login path
            $user->deleted_at      = Carbon::now();  // records when deletion happened
            $user->save();

            DB::commit();

            return response([
                'success' => true,
                'message' => 'Your account has been deleted. Your records are retained securely ' .
                             'for the period required by financial regulations.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response(['success' => false, 'message' => 'Could not delete account. Please try again later.'], 200);
        }
    }


   public function levelServicePricing(Request $request){
         $data = $request->all();
         $validatedData  = Validator::make($data, [
            'service' => 'required',
            'level_id' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $level = UserLevel::where("id", $request->level_id)->first();
        if(!$level){
                return response(['success' => false, 'message' => 'Package not found']);
        }
        if($level->status == "0"){
            return response(['success' => false, 'message' => 'Package not available try again later']);
        }

        $type = $request->service;
        $category = $level->name;

    if($type == "Airtime"){
        $item = Airtime::where("status", "1")->get();
        $response = " <br/>Airtime commission For $category <hr/>";
        foreach ($item as $value) {
            $response .= "$value->network - $value->type - {$value->iSmartLevelPricing($level->id)}%
            <hr/>";
        }
        return $response;
    }
   else if($type == "Airtime to Cash"){
        $item = AirtimeToCash::where("status", "1")->get();
        $response = " <br/>Airtime to Cash commission For $category <hr/>";
        foreach ($item as $value) {
            $response .= "{$value->network->name}  - {$value->iSmartLevelPricing($level->id)}%
            <hr/>";
        }
        return $response;
    }
    else if($type == "Data"){
        $item = Data::where("status", "1")->get();
        $response = " <br/>Data Price for $category <hr/>";
        foreach ($item as $value) {
            $response .= "$value->network  - $value->type - $value->name ₦{$value->iSmartLevelPricing($level->id)}
            <hr/>";
        }
        return $response;
    }
    else if($type == "Electricity"){
        $item = Bill::where("status", "1")->get();
        $response = " <br/>Electricity commission For $category <hr/>";
        foreach ($item as $value) {
            $response .= "$value->disco - $value->name - {$value->iSmartLevelPricing($level->id)}%
            <hr/>";
        }
        return $response;
      }
    else if($type == "Cable"){
        $item = Cable::where("status", "1")->get();
        $response = " <br/>Cable Price for $category <hr/>";
        foreach ($item as $value) {
            $response .= "{$value->cableType->name} - $value->name ₦{$value->iSmartLevelPricing($level->id)}
            <hr/>";
        }
        return $response;
        }
    else if($type == "Exam"){
        $item = Exam::where("status", "1")->get();
        $response = " <br/>Exam Pin Price for $category <hr/>";
        foreach ($item as $value) {
            $response .= "$value->name ₦{$value->iSmartLevelPricing($level->id)}
            <hr/>";
        }
        return $response;
    }





   }


   public function upgradeLevelList(Request $request){
    $user = Auth::user();
    $upgardeLevel = UserLevel::where("status", "1")->get();

    if(count($upgardeLevel) < 1){
        return response(['success' => false, 'message' => "No Upgrade Level available"]);
    }
    //$upLevel = null;
    //$serviceRes = null;

   foreach ($upgardeLevel as $key => $level) {
    $serviceRes[$key] = "";
    $data = Data::where("status", "1")->first();
    if($data){
        $serviceRes[$key] .= "Mobile Data: $data->network  - $data->type - $data->name ₦{$data->iSmartLevelPricing($level->id)}\n";
    }

    $airtime = Airtime::where("status", "1")->first();
    if($airtime){
        $serviceRes[$key] .= "Airtime: $airtime->network - $airtime->type - {$airtime->iSmartLevelPricing($level->id)}%\n";
    }

    $airtimeToCash = AirtimeToCash::where("status", "1")->first();
    if($airtimeToCash){
        $serviceRes[$key] .="Airtime To Cash: {$airtimeToCash->network->name}  - {$airtimeToCash->iSmartLevelPricing($level->id)}%\n";
    }

    $bill = Bill::where("status", "1")->first();
    if($bill){
        $serviceRes[$key] .= "Bill Payment: $bill->disco - $bill->name - {$bill->iSmartLevelPricing($level->id)}%\n";
    }

    $cable = Cable::where("status", "1")->first();
    if($cable){
        $serviceRes[$key] .= "Cable Subscription: {$cable->cableType->name} - $cable->name ₦{$cable->iSmartLevelPricing($level->id)}\n";
    }

    $exam = Exam::where("status", "1")->first();
    if($exam){
        $serviceRes[$key] .= "Exam Pin: $exam->name ₦{$exam->iSmartLevelPricing($level->id)}\n";
    }



    $upLevel[$key]["level_details"] = $level;
     $upLevel[$key]["service_details"] = $serviceRes[$key];

   }

    return response(['success' => true, 'message' => "available upgrade levels", "data" => $upLevel]);

   }


   public function upgradeAccount(Request $request){
    $user = Auth::user();
    $date = Carbon::now('Africa/Lagos');
    $data = $request->all();
        $validatedData  = Validator::make($data, [
            'package_id' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        if($user->user_level_id == $request->package_id){
            return response(['success' => false, 'message' => "You are already on this package"]);
        }

        $level = UserLevel::where("id", $request->package_id)->first();

        if(!$level){
            return response(['success' => false, 'message' => "Upgrade Level not found"]);
        }

        if($level->status == "0"){
            return response(['success' => false, 'message' => "Upgrade Level not available"]);
        }

         //check user Wallet
         $checkWallet =  checkUserWallet($level->amount);
         if (!$checkWallet) {
             return response(['success' => false, 'message' => "Insufficient Funds"]);
         }


        $amount = $level->amount;
        $wallet = $user->balance;
        $balance = $wallet - $amount;
        $description = "Account Upgarde To $level->name";
        if (updateUserBalance($balance)) {

            $Trx = strtoupper(Str::random(16));
            $ul['service'] = "Upgrade";
            $ul['service_type'] = "Account Upgarde";
            $ul['description'] = $description;
            $ul['amount'] = $amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $wallet;
            $ul['final_balance'] = $balance;
            $ul['reference'] = $Trx;
            $ul['status'] = "success";
            $tansaction =    $user->transaction()->create($ul);
        }

        if(!$tansaction){
            return response(['success' => false, 'message' => "An error occure please try again later"]);
        }

        $user->user_level_id = $level->id;
        $user->save();

            if (getSiteSettings("email_notify") == "Yes") {

                try {
                    $this->sendMail($user->email, $user->profile->first_name, 'Account Upgrade', $description);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            if (getSiteSettings("phone_notify") == "Yes") {
                try {
                    $this->sendSms($user->phone_number, $description);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            return response()->json([
                'success' => true,
                'code' => 200,
                'status' => 'successful',
                'message' => "Account Upgarded Successfully",
                'nextUrl' => route("UserTransactionDetails",$Trx),
                'data' => [
                    'service' => "Account Upgrade",
                    'Level' => $level->name,
                    'amount' => $amount,
                    'date' => $date,
                    'reference' => $Trx,
                    'wallet_balance' => $balance,
                ],
                "user" => $user,
            ]);



   }

   public function updateProfileField(Request $request)
   {
    $user = Auth::user();
    $data = $request->all();
    $column = request("column");
        $validatedData  = Validator::make($data, [
            "column" => 'required',
            "$column" => 'required',
            "table" =>'required'
        ]);

       if ($validatedData->fails()) {
        $error = "";
        foreach ($validatedData->errors()->all() as $key => $value) {
            $error .= $value;
        }
        return response(['success' => false, 'message' => $error]);
    }

    $item = request()->except('table','column');

    if($request->table == "user"){
        $user->fill($item)->save();
    }else{
        $user->profile->fill($item)->save();
    }

    $user->profile;

    return response(['success' => true, 'message' => 'Updated Successfully.', "user" => $user]);

   }

   public function updateProfile(Request $request)
   {
      $user = Auth::user();
       $data = $request->all();
        $validatedData  = Validator::make($data, [
            'username' => 'required|alpha_dash',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone_number' => "required|max:11|min:11",
            'email' => "required|email",
        ]);

       if ($validatedData->fails()) {
        $error = "";
        foreach ($validatedData->errors()->all() as $key => $value) {
            $error .= $value;
        }
        return response(['success' => false, 'message' => $error]);
    }

    $changePhone = "no";
    $changeEmail = "no";
    $checkPhone = User::where([
        ["phone_number", "=", $request->phone_number],
        ["id", "!=", $user->id]
    ])->first();

    if($checkPhone){
        return response(['success' => false, 'message' => "Phone Number already taken by other user"]);
    }else{
        //if the phone numebr is changed
        if($request->phone_number != $user->phone_number){
            if (getSiteSettings("phone_verify") == "Yes"){
                $phone_verify = "0";
            }else{
                $phone_verify = "1";
            }
            $phone_code = random_int(1000, 9999);
            $user->phone_code = $phone_code;
            $user->phone_time = Carbon::parse()->addMinutes(5);
            $user->phone_verify = $phone_verify;
            $changePhone = "Yes";
        }
    }

    //check email
    $checkEmail = User::where([
        ["email", "=", $request->email],
        ["id", "!=", $user->id]
    ])->first();

    if($checkEmail){
        return response(['success' => false, 'message' => "Email already taken by other user"]);
    }else{
        //if the email is changed
        if($request->email != $user->email){
            if (getSiteSettings("email_verify") == "Yes"){
                $email_verify = "0";
            }else{
                $email_verify = "1";
            }
            $email_code = strtoupper(Str::random(6));
            $email_time = Carbon::parse()->addMinutes(5);
            $user->email_verify = $email_verify;
            $user->email_code = $email_code;
            $user->email_time = $email_time;
            $changeEmail = "Yes";
        }

    }

    //check username
    $checkUser = Profile::where([
        ["username", "=", $request->username],
        ["user_id", "!=", $user->id]
    ])->first();

    if($checkUser){
        return response(['success' => false, 'message' => "Username already taken by other user"]);
    }
       try {
           $user->email = $request->email;
           $user->phone_number = $request->phone_number;
           $user->profile->username = $request->username;
           $user->profile->first_name = $request->first_name;
           $user->profile->last_name = $request->last_name;
           $user->profile->save();
           $user->save();

           if (getSiteSettings("email_verify") == "Yes" && $user->email != null && $changeEmail == "Yes")
           {
               $email_code = strtoupper(Str::random(6));
               $text = $email_code;
               try {
                   $this->sendCMail($user->email,$user->profile->name,'Email verification',$text);
               } catch (\Throwable $th) {
                   //throw $th;
               }
           }


           if (getSiteSettings("phone_verify") == "Yes" && $user->phone_number != null && $changePhone == "Yes")
           {
               try {
                   $this->sendOTP($user->phone_number,$phone_code);
               } catch (\Throwable $th) {
                   //throw $th;
               }
           }


        return response(['success' => true, 'message' => 'Profile Updated Successfully.', "user" => $user]);

       } catch (\Throwable $th) {
           return response(['success' => false, 'message' => "An Error Occure Contact Admin"]);
    }

   }


   public function findUser($search = null)
   {
    $search = request("search");
   $user = Auth::user();
   if($search == null){
           return response(['success' => false, 'message' => "Search filed is require"]);
   }

   $result = User::where('phone_number', $search)->orWhere('email', "$search")->OrwhereHas('profile', function (Builder $query) use ($search) {
    $query->where('username', $search);
    })->with('profile')->first();

   if(!$result){
       return response(['success' => false, 'message' => "No match found"]);
   }

   return response(['success' => true, 'message' => "Search Result", "data" => ["id"=> $result->id,  "username" => $result->profile->username ?? "", "name" => $result->profile->getName()  ] ]);
   }


   public function getUser($search)
   {

   $user = Auth::user();
   if($search == null){
           return response(['success' => false, 'message' => "Search filed is require"]);
   }

   $item = User::where('phone_number', $search)->orWhere('email', "$search")->OrwhereHas('profile', function (Builder $query) use ($search) {
    $query->where('username', $search);
    })->with('profile')->first();

   if(!$item){
       return response(['success' => false, 'message' => "No match found"]);
   }

   return response(['success' => true, 'message' => "Search Result", "data" => $item ]);
   }


   public function searchUsers($search)
    {
    // $user = Auth::user();
    // if($search == null){
    //         return response(['success' => false, 'message' => "Search filed is require"]);
    // }
    //  $result = Search::
    //   add(User::with('profile'), ['phone_number', 'email', 'profile.username', 'profile.first_name', 'profile.last_name'])
    //   ->beginWithWildcard()
    //   ->soundsLike()
    //   ->includeModelType('model_type')
    // ->paginate(10)
    // ->get($search)
    // //->groupBy('model_type')
    // ;

    // if(count($result ) < 1){
    //     return response(['success' => false, 'message' => "No match found"]);
    // }

    // return response(['success' => true, 'message' => "Search Result", "data" => $result ]);
        $user = Auth::user();
        if($search != null){
            $item = User::where('phone_number', "like", "%$search%")->orWhere('email', "like", "%$search%")->OrwhereHas('profile', function (Builder $query) use ($search) {
                $query->where('username', $search)->orWhere('first_name', "like", "%$search%")->orWhere('last_name', "like", "%$search%");
                })

            ->with('profile')->latest()->paginate(40);
        }else{
            $item = User::with('profile')->latest()->paginate(40);
        }

        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No User Found"]);
        }
        return response(['success' => true, "data" => $item,   "user" => $user, 'stats' => getUSerStats($user)]);
    }


    public function allUsers()
    {
        $user = Auth::user();
        //get all users for search
        $user = User::where("id", "!=", $user->id)->whereHas('profile', function (Builder $query) {
            $query->where('username', "!=", null);
        })->with("profile")->latest()->paginate(10);

        if (count($user) < 1) {
            return response(['success' => false, 'message' => "No User found"]);
        }
        return response(['success' => true, "data" => $user]);
    }

    /***
     * 
     * 
     * POS Request
     * 
     * 
     * 
     */

     public function posRequestDetails(){
        $user = Auth::user();

        if($user->posRequest == null){
            return response()->json([
                'success' => true,
                'message' => 'You are yet to submit post request',
                'data' => [],
                'user' => $user
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $user->posRequest,
            'user' => $user,
        ]);

     }


     public function submitPOSRequest(Request $request){

        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'business_address' => 'required|string|max:255',
            'number_of_terminal' => 'required|string',
            'transaction_volume' => 'required',
            'address_profile' => 'image',
        ]);

        if($validatedData ->fails()){
            $error = "";
            foreach ($validatedData ->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();
        $item = request()->except('address_profile');

        if(request('address_profile')){
            $imagePath = request('address_profile')->store('posrequest/image','public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $item["file_one"] = $imagePath;
            if($user->posRequest != null && $user->posRequest->image != null){
                if (File::exists(public_path("storage/{$user->posRequest->file_one}"))) {
                    File::delete(public_path("storage/{$user->posRequest->file_one}"));

               }
            }
        }


        if($user->posRequest != null){
            $item["status"] = "0";
            $user->posRequest->fill($item)->save();
        }else{
            $user->posRequest()->create($item);
        }

        return response()->json([
            'success' => true,
            'data' => $user->posRequest,
            'message' => 'POS Request Information Submited Successfully',
            'user' => $user,

        ]);

         }



    /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *          Bank and Virtual Account
     *
     *
     */
     
    // public function createVirtualAccount($email = null)
    // {
    //     $user = Auth::user();

    //     //if user is yet to has an email address
    //     if($user->email == null){
    //         if($email == null){
    //             return response(['success' => false, 'message' => "Email is required"]);
    //         }
    //     $checkEmail = User::where([
    //         ["email", "=", $email],
    //         ["id", "!=", $user->id]
    //     ])->first();

    //     if($checkEmail){
    //         return response(['success' => false, 'message' => "Email has already been used by another user"]);
    //     }
    //     $user->email = $email;
    //     $user->save();
    //     }

    //     /*$virtualAccountResult = (new iSmartMonnify())->generateVirtualAccount($user->id);*/
        
    //     //$virtualAccountResult = (new iSmartPaystack())->createDedicatedVirtualAccount($user);
        
        
        
    //      if($user->walletVerification == null){
    //         return response(['success' => false, 'message' => "KYC Verification is required"]);
    //     }
        
    //     $virtualAccountResult = (new iSmartFlutterWave())->generatVirtualAccount($user, $user->walletVerification->bvn, true);

    //     if (!$virtualAccountResult || !$virtualAccountResult["success"]) {
    //         return response(['success' => false, 'message' => "Unable to create virtual account contact help desk", $virtualAccountResult]);
    //     }

    //   /* if($virtualAccountResult == null){
    //         return response(['success' => false, 'message' => "Unable to create virtual account contact help desk"]);

        

    //     if($virtualAccountResult["success"] == null || !$virtualAccountResult["success"]){
    //         return response(['success' => false, 'message' => "Unable to create virtual account try again later", $virtualAccountResult["message"]]);
    //     }*/
    //     $user = User::whereId(auth()->user()->id)->with('profile')->with('virtualAccount')->first();
    //     return response(
    //         ['success' => true,
    //         "data" => [
    //         'message' => "Virtual Account Created Successfully",
    //         'wallet_balance' => $user->balance,
    //         ],
    //         "user" => $user,
    // ]);
    // }
    
    
    
    
    public function createVirtualAccount()
    {
        $user = Auth::user();
    
        // Ensure KYC is done
        if ($user->walletVerification == null) {
            return response()->json([
                'success' => false,
                'message' => "KYC Verification is required"
            ], 403);
        }
    
        try {
            $dateString = $user->walletVerification->date_of_birth;
            $formattedDate = Carbon::createFromFormat('Y-m-d', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Invalid date format in wallet verification record"
            ], 422);
        }
    
        $virtualAccountResult = createSafeHavenSubaccount([
            'user_id' => $user->id,
            'phoneNumber' => $user->phone_number,
            'emailAddress' => $user->email,
            'identityType' => $user->walletVerification->method,
            'identityNumber' => $user->walletVerification->bvn,
            'externalReference' => rand(1000000, 999999999),
            'dob' => $formattedDate,
        ]);
    
        // Validate API response
        if (
            !$virtualAccountResult ||
            !isset($virtualAccountResult['success']) ||
            $virtualAccountResult['success'] === false
        ) {
            return response()->json([
                'success' => false,
                'message' => "Unable to create virtual account. Contact help desk.",
                'data' => $virtualAccountResult,
            ], 500);
        }
    
        // Get latest user data
        $user = User::whereId($user->id)
            ->with(['profile', 'virtualAccount'])
            ->first();
    
        return response()->json([
            'success' => true,
            'data' => [
                'message' => "Virtual Account Created Successfully",
                'wallet_balance' => $user->balance,
            ],
            'user' => $user,
        ]);
    }

    public function getVirtualAccount()
    {
        $user = Auth::user();
    
        $bank = VirtualAccount::where('user_id', $user->id)->first();
    
        if ($bank) {
            return response(['success' => true, 'data' => $bank]);
        }
    
        if (!$user->walletVerification || !$user->walletVerification->status) {
            return response(['success' => false, 'message' => "No Bank Account Found"]);
        }
    
        try {
            $formattedDate = Carbon::createFromFormat(
                'Y-m-d',
                $user->walletVerification->date_of_birth
            )->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Invalid date format in wallet verification record"
            ], 422);
        }
    
        $virtualAccountResult = createSafeHavenSubaccount([
            'user_id' => $user->id,
            'phoneNumber' => $user->phone_number,
            'emailAddress' => $user->email,
            'identityType' => $user->walletVerification->method,
            'identityNumber' => $user->walletVerification->bvn,
            'externalReference' => rand(1000000, 999999999),
            'dob' => $formattedDate,
        ]);
    
        if (empty($virtualAccountResult) || ($virtualAccountResult['success'] ?? false) === false) {
    
            ErrorLog::create([
                "user_id" => $user->id,
                "user_name" => optional($user->profile)->first_name . ' ' . optional($user->profile)->last_name,
                "message" => $virtualAccountResult['message'] ?? "Unable to create virtual account.",
                "model" => "Create Virtual Account",
                "data" => json_encode($virtualAccountResult),
            ]);
    
            return response(['success' => false, 'message' => "No Bank Account Found"]);
        }
    
        $bank = VirtualAccount::where('user_id', $user->id)->first();
    
        return response(['success' => true, 'data' => $bank]);
    }

    public function getBankAccount()
    {
        $user = Auth::user();
        $item = $user->bank()->latest()->get();
        if (count($item) < 1) {
            return response(['success' => false, 'message' => "No Bank Account Found"]);
        }
        return response(['success' => true,  "user" => $user, "data" => $item]);
    }

    public function storeBankAccount(Request $request)
    {
        $date = Carbon::now('Africa/Lagos');
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'bank_name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();
        $check = $user->bank()->where("account_number", $request->account_number)->first();
        if ($check) {
            return response(['success' => false, 'message' => "Account Details already Exist"]);
        }

        if ($user->bank->count() >= 5) {
            return response(['success' => false, 'message' => "You can only add Maximum of 5 Bank Account"]);
        }

        $item =   $user->bank()->create($data);

        return response(['success' => true, 'wallet_balance' => $user->balance,  "user" => $user,  "data" => $item]);
    }

    public function distoryBankAccount(Request $request, $id)
    {

        $user = Auth::user();
        $bank = $user->bank()->where("id", $id)->first();
        if (!$bank) {
            return response(['success' => false, 'message' => "Bank Account might have been deleted already"]);
        }
        $checkWithdrawal = $bank->withdraw()->where("status", "0")->orWhere("status", "2")->first();
        if ($checkWithdrawal) {
            return response(['success' => false, 'message' => "Sorry this has an active withdrawal request"]);
        }
        $bank->delete();
        return response()->json([
            'success' => true,
            "user" => $user,
            'code' => 200,
            'status' => 'successful',
            'data' => [
                'wallet_balance' => $user->balance,
            ],
        ]);
    }
    
    public function getTransaction($search = null)
{
    $user = auth()->user();

    if (!$user) {
        return response([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $query = $user->transaction()
        ->select('transactions.*');

    // SEARCH
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('phone_number', 'like', "%{$search}%")
              ->orWhere('reference', 'like', "%{$search}%")
              ->orWhere('paymentReference', 'like', "%{$search}%")
              ->orWhere('service_type', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // DATE FILTER
    if (request()->filled('start_date')) {

        $query->where(
            'created_at',
            '>=',
            request('start_date') . ' 00:00:00'
        );
    }

    if (request()->filled('end_date')) {

        $query->where(
            'created_at',
            '<=',
            request('end_date') . ' 23:59:59'
        );
    }

    $items = $query
        ->latest('created_at')
        ->paginate(20);

    if ($items->isEmpty()) {
        return response([
            'success' => false,
            'message' => 'No Transaction Found'
        ]);
    }

    return response([
        'success' => true,
        'stats' => getUSerStats($user),
        'data' => $items,
        'user' => $user
    ]);
}

//     public function getTransaction($search = null)
//     {
//         $user = Auth::user();
//         $today = Carbon::today()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
//         $todayEnd = Carbon::today()->setTime(12, 59, 0)->format('Y-m-d H:i:s');


//         $startDate = Carbon::parse(request('start_date') ?? $today);
//         $endDate = Carbon::parse(request('end_date') ?? $todayEnd);

//         if($search != null){
//             $item = $user->transaction()->where('phone_number', "like", "%$search%")->orWhere('reference', "like", "%$search%")
//             ->orWhere('paymentReference', "like", "%$search%")
//             ->orWhere('service_type', "like", "%$search%")
//             ->orWhere('description', "like", "%$search%")
//             ->when(request('start_date') || request('end_date'), function ($query) {
//     $startDate = request('start_date');
//     $endDate = request('end_date');

//     return $query->whereBetween('created_at', [
//         Carbon::parse($startDate)->startOfDay(),
//         Carbon::parse($endDate)->endOfDay()
//     ]);
// })

//             ->latest()->paginate(20);
//         }else{
//             $item = $user->transaction()
//             ->when(request('start_date') || request('end_date'), function ($query, $startDate, $endDate) {
//                 return $query ->whereBetween('created_at', [date('Y-m-d H:i:s', strtotime($startDate)), date('Y-m-d 23:59:59', strtotime($endDate))]);
//             }, function ($query) {
//                 return $query;
//             })
//             ->latest()->paginate(20);
//         }

//         if (count($item) < 1) {
//             return response(['success' => false, 'message' => "No Transaction Found"]);
//         }
//         return response(['success' => true, 'stats' => getUSerStats($user),  "data" => $item,   "user" => $user]);
//     }
    
    public function getTransactionByDate(Request $request)
    {
        $user = Auth::user();
    
        if (!$user) {
            return response([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    
        $filter = $request->filter; 
        // expected: last_6_months, last_3_months, custom
    
        $query = $user->transaction();
    
        if ($filter === 'last_6_months') {
            $startDate = Carbon::now()->subMonths(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
    
            $query->whereBetween('created_at', [$startDate, $endDate]);
    
        } elseif ($filter === 'last_3_months') {
            $startDate = Carbon::now()->subMonths(3)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
    
            $query->whereBetween('created_at', [$startDate, $endDate]);
    
        } elseif ($filter === 'custom') {
            if (!$request->start_date || !$request->end_date) {
                return response([
                    'success' => false,
                    'message' => 'Start date and end date are required for custom filter'
                ], 422);
            }
    
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
    
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
    
        $transactions = $query->latest()->paginate(20);
    
        if ($transactions->isEmpty()) {
            return response([
                'success' => false,
                'message' => 'No Transaction Found'
            ]);
        }
    
        return response([
            'success' => true,
            'stats' => getUSerStats($user),
            'data' => $transactions,
            'user' => $user
        ]);
    }


    /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *     Transfer Fund
     *
     *
     */

    public function transferFund(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'wallet_id' => '',
            'reciever_id' => 'required',
            'amount' => 'required',
            'comment' => '',
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

        $select_wallet_id = $request->wallet_id;
        if (!checkWalletEnable("TransferFund", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }

        if ($user->id == $request->reciever_id) {
            return response(['success' => false, 'message' => "You cant Transfer fund to yourself"]);
        }

        if ($request->amount <= 1) {
            return response(['success' => false, 'message' => "Lol try harder"]);
        }

        if ($request->amount >= 10000) {
            return response(['success' => false, 'message' => "Sorry You cant do that"]);
        }

        $reciever = User::find($request->reciever_id);

        if (!$reciever) {
            return response(['success' => false, 'message' => "User not found"]);
        }

        if (getSiteSettings("enable_transfer") != "Yes") {
            return response(['success' => false, 'message' => "Sorry Fund Transfer is dissabled at the moment try again later"]);
        }

        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "System")
            {

                return response(['success' => false,  'message' => "Probably Duplicate Transaction please try again later",]);

            }
        }

        if ($request->comment != null) {
            $for = $request->comment;
        } else {
            $for = "";
        }

        //check user Wallet
        $charge = $user->level->transfer_charge / 100 * $request->amount;
        $amount =  $request->amount + $charge;
        $checkWallet =  checkUserWallet($amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Fund"]);
        }
        $wallet = $user->balance;
        $balance = $wallet - $amount;
        if (updateUserBalance($balance, $select_wallet_id)) {
            $description = "Transfer of $request->amount to " . $reciever->profile->username . " $for";
            $trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $user->user_level_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Fund Transfer";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $wallet;
            $ul['final_balance'] = $balance;
            $ul['reference'] = $trx;
            $ul['status'] = "pending";
            $sender_tansaction =  $user->transaction()->create($ul);

            $transer = $user->transferFund()->create([
                "reciever_id" => $request->reciever_id,
                "amount" => $request->amount,
                "charge" => $charge,
                'referrence' => $trx,
                "comment" => $request->comment,
                "status" => "1"
            ]);
        }else{
            return response(['success' => false, 'message' => "Wallet balance error"]);
        }



        //update the reciver balance
        $reciverBalance = $reciever->balance;

        if ($transer && updateOtherUserBalance($reciever, ($reciever->balance + $request->amount), $select_wallet_id)) {

            $description = "You Recieve sum of $request->amount naira from " . $user->profile->username . " $for";
            $trxR = strtoupper(Str::random(16));
            $ul['user_level_id'] = $reciever->user_level_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service'] = "System";
            // $ul['service_type'] = "Fund Transfer";
            $ul['service_type'] = "Wallet Funding";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $reciverBalance;
            $ul['final_balance'] = $reciverBalance + $request->amount;
            $ul['reference'] = $trxR;
            $ul['status'] = "pending";
            $receiver_tansaction =  $reciever->transaction()->create($ul);
        }


        //check if success and Update Record
        if ($sender_tansaction && $receiver_tansaction) {
            $querySend =  $user->transaction()->where(['id' => $sender_tansaction->id])->update(['status' => "success"]);
            $queryRecieve =  $reciever->transaction()->where(['id' => $receiver_tansaction->id])->update(['status' => "success"]);
            if ($querySend && $queryRecieve) {

                if (getSiteSettings("email_notify") == "Yes") {
                    try {
                        $formattedAmount  = number_format($request->amount, 2);
                        $formattedCharge  = number_format($charge, 2);
                        $formattedBalance = number_format($balance, 2);
                        $formattedReceiverBalance = number_format($recieverBalance + $request->amount, 2);
                        $dateTime = now()->format('d-M-Y h:i A');

                        // ── Sender email ──────────────────────────────────────────────
                        $senderEmail = "
                            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                            <table style='width:100%; border-collapse:collapse; font-family:Arial,sans-serif; font-size:14px;'>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Transaction Type</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>DEBIT (P2P Transfer)</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Recipient</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>@" . $reciever->profile->username . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Amount Sent</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>&#8358;{$formattedAmount}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Transaction Fee</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>&#8358;{$formattedCharge}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Transaction Reference</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>{$trx}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Date and Time</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>{$dateTime}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Available Balance</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>&#8358;{$formattedBalance}</td>
                                </tr>
                                " . (!empty($request->comment) ? "
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Narration</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($request->comment) . "</td>
                                </tr>" : "") . "
                            </table>
                            <br>
                            <p>Thank you for using Dupay.</p>
                        ";

                        // ── Receiver email ────────────────────────────────────────────
                        $receiverEmail = "
                            <p>This is to inform you that a transaction has occurred on your account with <strong>Dupay</strong> with details below:</p>
                            <table style='width:100%; border-collapse:collapse; font-family:Arial,sans-serif; font-size:14px;'>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Transaction Type</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>CREDIT (P2P Transfer)</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Sender</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>@" . $user->profile->username . "</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Amount Received</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>&#8358;{$formattedAmount}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Transaction Reference</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>{$trxR}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Date and Time</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>{$dateTime}</td>
                                </tr>
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Available Balance</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>&#8358;{$formattedReceiverBalance}</td>
                                </tr>
                                " . (!empty($request->comment) ? "
                                <tr>
                                    <td style='padding:10px; border:1px solid #ddd;'>Narration</td>
                                    <td style='padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($request->comment) . "</td>
                                </tr>" : "") . "
                            </table>
                            <br>
                            <p>Thank you for using Dupay.</p>
                        ";

                        $this->sendMail($user->email, $user->profile->first_name, 'Dupay Fund Transfer Notification', $senderEmail);
                        $this->sendMail($reciever->email, $reciever->profile->first_name, 'Dupay Fund Transfer Notification', $receiverEmail);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = "You Recieve sum of $request->amount naira from " . $user->profile->username . " Reference ID Is : #$trxR";
                    try {
                        $this->sendSms($reciever->phone_number, $text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    "user" => $user,
                    'status' => 'successful',
                    'nextUrl' => route("UserTransactionDetails",$trx),
                    'message' => "Transfer Successful",
                    'data' => [
                        'service' => "Fund Transafer",
                        'amount' => $request->amount,
                        "charge" => $charge,
                        'from' => $user->profile->username,
                        "to" => $reciever->profile->username,
                        "for" => $request->comment,
                        'date' => $date,
                        'reference' => $trx,
                        'wallet_balance' => $balance,
                    ],
                ]);
            }
        } else {
            $query =  $user->transaction()->where(['id' => $sender_tansaction->id])->update(['status' => "unsuccessful"]);
            if ($query) {
                $receiver_tansaction->delete();
                //refund user
                if (updateUserBalance($wallet, $select_wallet_id)) {
                    $description = "Transfer of $request->amount naira to " . $reciever->profile->username . " Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $reciever->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Fund Transfer";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
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
                    'referrence' => $trx,
                    'date' => $date
                ],
            ]);
        }
    }


    /**********************
     *      iSMart Olamilekan
     *         07038858824
     *
     *          Withdraw Fund
     *
     *
     */

    public function withdrawFund(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'wallet_id' => '',
            'bank_id' => 'required',
            'amount' => 'required',
            'comment' => '',
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


        $select_wallet_id = $request->wallet_id;
        if (!checkWalletEnable("WithdrawFund", $select_wallet_id)) {
            return response(['success' => false, 'message' => "Selected Wallet not allowed for this transaction"]);
        }

        $bank = BankAccount::find($request->bank_id);

        if (!$bank) {
            return response(['success' => false, 'message' => "Bank Details Not Found"]);
        }

        if (getSiteSettings("enable_withdrawal") != "Yes") {
            return response(['success' => false, 'message' => "Sorry Withdrawal is dissabled at the moment try again later"]);
        }


        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "System")
            {

                return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

            }
        }



        //check user Wallet
        $charge = $user->level->withdrawal_charge / 100 * $request->amount;
        $amount =  $request->amount + $charge;
        $checkWallet =  checkUserWallet($amount, $select_wallet_id);
        if (!$checkWallet) {
            return response(['success' => false, 'message' => "Insufficient Funds"]);
        }
        $wallet = $user->balance;
        $balance = $wallet - $amount;
        if (updateUserBalance($balance, $select_wallet_id)) {
            $description = "Withdrawal Request of $request->amount to your $bank->bank_name account";
            $trx = strtoupper(Str::random(16));
            $ul['user_level_id'] = $user->user_level_id;
            $ul['wallet_id'] = $select_wallet_id;
            $ul['service'] = "System";
            $ul['service_type'] = "Fund Withdrawal";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "0";
            $ul['initial_balance'] = $wallet;
            $ul['final_balance'] = $balance;
            $ul['reference'] = $trx;
            $ul['status'] = "pending";
            $tansaction =  $user->transaction()->create($ul);
        }

        if ($tansaction) {
            $withdraw = $user->withdrawalLog()->create([
                "bank_id" => $request->bank_id,
                "amount" => $request->amount,
                "charge" => $charge,
                'referrence' => $trx,
                "comment" => $request->comment,
                "status" => "0"
            ]);
        }


        //check if success and Update Record
        if ($withdraw) {
            //$query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "pending"]);
            //if ($query) {

                if (getSiteSettings("email_notify") == "Yes") {
                    $text = "You Withdraw sum of $request->amount naira into your " . $bank->bank_name . " account  Reference ID Is : #$trx";
                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Fund Withdrawal', $text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = "You Withdraw sum of $request->amount naira into your " . $bank->bank_name . " account  Reference ID Is : #$trx";
                    try {
                        $this->sendSms($user->phone_number, $text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => 'pending',
                     "user" => $user,
                    'data' => [
                        'service' => "Fund Withdrawal",
                        'amount' => $request->amount,
                        "charge" => $charge,
                        'from' => "Wallet",
                        "to" => "$bank->account_number $bank->bank_name",
                        'date' => $date,
                        'reference' => $trx,
                        'wallet_balance' => $balance,
                    ],
                ]);
            //}
        } else {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful"]);
            $wquery = $user->withdrawalLog()->where(['id' => $withdraw->id])->update(['status' => "4"]);
            if ($query && $wquery) {
                //refund user
                if (updateUserBalance($wallet, $select_wallet_id)) {
                    $description = "Withdrawal of $request->amount naira to " . $bank->bank_name . " Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['user_level_id'] = $user->user_level_id;
                    $ul['wallet_id'] = $select_wallet_id;
                    $ul['service'] = "System";
                    $ul['service_type'] = "Fund Withdrawal";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
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
                    'referrence' => $trx,
                    'date' => $date
                ],
            ]);
        }
    }


    /**
     *
     * Manual Deposit
     *
     */
    public function manualFunding(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'amount' => 'required',
            'proof' => 'required|image',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);

        }

        if ($request->amount <= 1) {
            return response(['success' => false, 'message' => "Lol try harder"]);
        }

        if ($request->amount < getSiteSettings("min_manual_deposit")) {
            return response(['success' => false, 'message' => getSiteSettings("min_manual_deposit")." Minimum Deposit "]);
        }

        if (getSiteSettings("enable_manual_deposit") != "Yes") {
            return response(['success' => false, 'message' => "Sorry Manual Deposit is dissabled at the moment try again later"]);
        }

        $user = Auth::user();

        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "System")
            {

                return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

            }
        }

        if(request('proof')){
            $imagePath = request('proof')->store('deposit','public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $ul['proof'] = $imagePath;
        }

            $charge = getSiteSettings("manual_deposit_charge");
            $amount =  $request->amount - $charge;
            $balance = $user->balance;
            $amountPaid = $request->amount;
            $trx = strtoupper(Str::random(16));
            $finalBalance = $balance + $amount;

            $trans =   $user->payment()->create([
                "payment_gateway_id" => 0,
                "type" => "Manual Method",
                "reference" => $trx,
                "amount" => $amountPaid,
                "status" => "pending",
                "date" => $date
            ]);

            $description = "Manual Deposit of ₦$request->amount to your wallet";

            $ul['user_level_id'] = $user->user_level_id;
            $ul['wallet_id'] = "1";
            $ul['service'] = "System";
            $ul['service_type'] = "Wallet Funding";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $balance;
            $ul['final_balance'] = $finalBalance;
            $ul['reference'] = $trx;
            $ul['status'] = "pending";
            $tansaction =  $user->transaction()->create($ul);

            if(!$trans || !$tansaction){
                return response()->json([
                    'success' => false,
                    'status' => 'unsuccessful',
                    'message' => 'Transaction unsuccessful',
                    'data' => [
                        'referrence' => $trx,
                        'date' => $date
                    ],
                ]);
            }


            try {
                //notify admin by email
                $tansProof = url('/')."/storage/".$tansaction->proof;
                $proof = "<img src='$tansProof'/>";
                $adminMessage = $user->profile->username." Just deposited the sum of ₦$amountPaid using manual funding\n\nPhone Number: $user->phone_number\n\nEmail:$user->email\n\nRef ID: $trx\n\n$proof";
                $this->sendMail(getSiteSettings("email"), "Admin", "Manual Deposit of ₦$amountPaid", $adminMessage);

                if (getSiteSettings("email_notify") == "Yes") {
                    $text = $description . " is processing Reference ID Is : $trx";
                    $this->sendMail($user->email, $user->profile->first_name, "₦$amountPaid Account Deposit", $text);
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = $description . " Reference ID Is : $trx";
                    $this->sendSms($user->phone, $text);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }


            return response()->json([
                'success' => true,
                'code' => 200,
                'status' => 'pending',
                'data' => [
                    'service' => "Wallet Funding",
                    'amount' => $request->amount,
                    "charge" => $charge,
                    'date' => $date,
                    'reference' => $trx,
                    'wallet_balance' => $finalBalance,
                ],
                "user" => $user,
            ]);

    }



    /***
     *
     *
     * support
     *
     *
     */

    public function storeSupport(Request $request)
    {
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'support_type_id' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'attachment' => 'image',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();


        $check = $user->support()->where("subject", $request->subject)->first();
        if ($check && $check->status != "4") {
            return response(['success' => false, 'message' => "Ticket already open"]);
        }
        $ticket_number = Str::random(5);

        if($request->hasFile('attachment')){
            $imagePath = request('attachment')->store("support", 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $attachment = $imagePath;
        }

        $data["ticket_number"] = $ticket_number;
        $item =   $user->support()->create($data);

        if($item){
            $item->message()->create([
                "sender" => "0",
                "message" => $request->message,
                "attachment" => $attachment ?? null
            ]);
        }

        return response(['success' => true,  "user" => $user, "data" => [
            'message' => "Ticket Open Successfully",
            'ticket_number' => $ticket_number,
        ]]);
    }




    public function storeSupportMessage(Request $request, $ticketNumber)
    {
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'message' => 'required',
            'attachment' => 'image',
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $user = Auth::user();


        $support = $user->support()->where("ticket_number",$ticketNumber)->first();
        if (!$support) {
            return response(['success' => false, 'message' => "Support ticket not found"]);
        }


        if($request->hasFile('attachment')){
            $imagePath = request('attachment')->store("support", 'public');
            $image = Image::make(public_path("storage/{$imagePath}"));
            $image->save();
            $attachment = $imagePath;
        }

        if($support->status == "0" || $support->status == "1"){
            $support->status = $support->status;
        }else{
            $support->status = "2";
        }

         $messageContent = $support->message()->create([
                "sender" => "0",
                "message" => $request->message,
                "attachment" => $attachment ?? null
            ]);

        if($messageContent){
            $support->save();
        }

        return response(['success' => true,  "user" => $user,  "data" => [
            'message' => "Message sent Successfully",
            'ticket_number' => $ticketNumber,
            'content' => $messageContent,
        ]]);
    }




    public function redeemCommission(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'amount' => 'required'
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }
        $TranxMessage = null;
        $user = Auth::user();
        $finalStatus = "pending";


        //check transaction pin
        // if ($request->header('HTTP_ISMART') == null || $request->header('HTTP_ISMART') != 'api') {
        //     if ($request->transaction_pin == null || $request->transaction_pin == "") {
        //         return response(['success' => false, 'message' => "Please Enter Transaction Pin"]);
        //     }
        //     elseif ($user->transaction_pin == null || $user->transaction_pin == "") {
        //         return response(['success' => false, 'message' => "Transaction Pin not set"]);
        //     }
        //     elseif (!Hash::check($request->transaction_pin, $user->transaction_pin)) {
        //         return response(['success' => false, 'message' => "Incorrect Transaction Pin"]);
        //     }
        // }
        
        
        

        if (getSiteSettings("enable_commission_redemption") != "Yes") {
            return response(['success' => false, 'message' => "Sorry Commission Redemption is dissabled at the moment try again later"]);
        }


        if ($request->amount < getSiteSettings("min_commission_redemption")) {
            return response(['success' => false, 'message' => "Sorry you cant redeem less than ".getSiteSettings("min_commission_redemption")]);
        }

        if ($request->amount > getSiteSettings("max_commission_redemption")) {
            return response(['success' => false, 'message' => "Sorry you cant redeem more than ".getSiteSettings("max_commission_redemption")]);
        }

        if ($request->amount > $user->profile->commission) {
            return response(['success' => false, 'message' => "Insufficient Commission Balance"]);
        }


        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "System")
            {

                return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

            }
        }


        $commissionWallet = $user->profile->commission;
        $wallet = $user->balance;


        $user->profile->commission  = $commissionWallet - $request->amount;
        $user->profile->save();
        if (getSiteSettings("auto_commission_redemption") == "Yes"){
            $balance = $wallet + $request->amount;
            $finalStatus = "success";
        }else{
            $finalStatus = "pending";
            $balance = $wallet;
        }

        if (updateUserBalance($balance)) {
            $description = "Commission Redemption Request of $request->amount naira to your wallet";
            $trx = strtoupper(Str::random(16));
            $ul['service'] = "System";
            $ul['service_type'] = "Commission Redeem";
            $ul['description'] = $description;
            $ul['amount'] = $request->amount;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $wallet;
            $ul['final_balance'] = $balance;
            $ul['reference'] = $trx;
            $ul['status'] = $finalStatus;
            $tansaction =  $user->transaction()->create($ul);
        }

        if ($tansaction) {
            $redeem = true; 
            
            // $user->commissionlLog()->create([
            //     "amount" => $request->amount,
            //     'referrence' => $trx,
            //     "status" => "0"
            // ]);
        }


        //check if success and Update Record
        if ($redeem) {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => $finalStatus]);
            //if ($query) {

                if (getSiteSettings("email_notify") == "Yes") {
                    $text = "You Redeem sum of $request->amount naira of your referral commission into your wallet  Reference ID Is : #$trx";
                    try {
                        $this->sendMail($user->email, $user->profile->first_name, 'Fund Withdrawal', $text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if (getSiteSettings("phone_notify") == "Yes") {
                    $text = "You Withdraw sum of $request->amount naira of your referral commission into your wallet Reference ID Is : #$trx";
                    try {
                        $this->sendSms($user->phone_number, $text);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => $finalStatus,
                    "user" => $user,
                    'data' => [
                        'service' => "Commission Redeem",
                        'amount' => $request->amount,
                        'date' => $date,
                        'reference' => $trx,
                        'wallet_balance' => $balance,
                        'commission_balance' => $user->profile->commission,
                    ],
                ]);
            //}
        } else {
            $query =  $user->transaction()->where(['id' => $tansaction->id])->update(['status' => "unsuccessful"]);
              $wquery = $user->commissionlLog()->where(['id' => $redeem->id])->update(['status' => "4"]);
            if ($query && $wquery) {
                //refund user
                if (updateUserBalance($wallet)) {
                    $description = "Commission of $request->amount naira to wallet Refund";
                    $trx = strtoupper(Str::random(16));
                    $ul['service'] = "System";
                    $ul['service_type'] = "Commission Redeem";
                    $ul['description'] = $description;
                    $ul['amount'] = $request->amount;
                    $ul['phone_number'] = $user->phone_number;
                    $ul['type'] = "1";
                    $ul['initial_balance'] = $balance;
                    $ul['final_balance'] = $wallet;
                    $ul['reference'] = $trx;
                    $ul['status'] = "refund";
                    $tansaction =  $user->transaction()->create($ul);

                    $user->profile->commission  = $commissionWallet;
                    $user->profile->save();
                }
            }
            return response()->json([
                'success' => false,
                'status' => 'unsuccessful',
                'message' => 'Transaction unsuccessful',
                'data' => [
                    'referrence' => $trx,
                    'date' => $date
                ],
            ]);
        }

    }





    public function rewardVideoCommission(Request $request)
    {
        $date = Carbon::now();
        $data = $request->all();
        $validatedData  = Validator::make($data, [
            'amount' => 'required'
        ]);

        if ($validatedData->fails()) {
            $error = "";
            foreach ($validatedData->errors()->all() as $key => $value) {
                $error .= $value;
            }
            return response(['success' => false, 'message' => $error]);
        }

        $user = Auth::user();

        if (!isset($_SERVER['HTTP_ISMART']) || $_SERVER['HTTP_ISMART'] != "app") {
            return response(['success' => false, 'message' => "Sorry you must make use of our mobile application"]);
        }

        if (getSiteSettings("enable_video_reward") != "Yes") {
            return response(['success' => false, 'message' => "Sorry video reward is dissabled at the moment try again later"]);
        }

        $TransactionCount = $user->transaction()->where("service_type", "Video Watch Bonus")->whereDate('created_at', Carbon::today())->count();

        if ($TransactionCount >= getSiteSettings("video_reward_count")) {
            return response(['success' => false, 'message' => "Sorry you have reach today watch limit"]);
        }

        $userTransaction = $user->transaction()->latest()->first();
        if($userTransaction){
            if ($userTransaction->created_at > Carbon::now()->subMinutes(1)  &&  $userTransaction->service ==  "System")
            {

                return response(['success' => false, 'message' => "Probably Duplicate Transaction please try again later",]);

            }
        }


            $bonusWallet = $user->bonus;
            $vBonus = getSiteSettings("video_watch_reward_amount");
            $min= 0.1;
            $max= $vBonus;
            //$vBonus = rand($min,$max);
            $vBonus = float_rand($min, $max);
            $user->bonus  = $bonusWallet + $vBonus;


            $description = "Congratulations!!! you recieved ".$vBonus." as Video Watch Bonus $request->amount";
            $trx = strtoupper(Str::random(16));
            $ul['service'] = "System";
            $ul['wallet_id'] = "2";
            $ul['service_type'] = "Video Watch Bonus";
            $ul['description'] = $description;
            $ul['amount'] = $vBonus;
            $ul['phone_number'] = $user->phone_number;
            $ul['type'] = "1";
            $ul['initial_balance'] = $bonusWallet;
            $ul['final_balance'] = $bonusWallet + $vBonus;
            $ul['reference'] = $trx;
            $ul['status'] = "success";
            $tansaction =  $user->transaction()->create($ul);


        //check if success and Update Record
        if ($tansaction) {
            $user->save();

                // if (getSiteSettings("email_notify") == "Yes") {
                //     $text = "You recieved sum of ". getSiteSettings("video_watch_reward_amount") ." naira as video watch bonus Reference ID Is : #$trx";
                //     try {
                //         $this->sendMail($user->email, $user->profile->first_name, 'Video Watch Bonus', $text);
                //     } catch (\Throwable $th) {
                //         //throw $th;
                //     }
                // }
                // if (getSiteSettings("phone_notify") == "Yes") {
                //     $text = "You recieved sum of ". getSiteSettings("video_watch_reward_amount") ." into your wallet Reference ID Is : #$trx";
                //     try {
                //         $this->sendSms($user->phone_number, $text);
                //     } catch (\Throwable $th) {
                //         //throw $th;
                //     }
                // }

                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'status' => "success",
                    'data' => [
                        'service' => "Video Watch Bonus",
                        'amount' => getSiteSettings("video_watch_reward_amount"),
                        'date' => $date,
                        'reference' => $trx,
                        'initial_balance' => $bonusWallet,
                        'final_balance' => $user->bonus,
                    ],
                    "user" => $user,
                ]);
            //}
        } else {
            return response()->json([
                'success' => false,
                'status' => 'unsuccessful',
                'message' => 'Unable to log up video watch bonus',
            ]);
        }


    }

    public function toggleSms(Request $request)
    {
        $validated = $request->validate([
            'receiveSMS' => ['required', 'boolean'],
        ]);
    
        $user = Auth::user();
    
        // Ensure profile exists
        if (!$user->profile) {
            return response()->json([
                'status' => false,
                'message' => 'User profile not found.',
                'data' => [
                    'receiveSMS' => false,
                ],
            ], 404);
        }
    
        // Update profile
        $user->profile()->update([
            'receive_sms' => $validated['receiveSMS'],
        ]);
    
        // Return a clean boolean (true/false)
        return response()->json([
            'success' => true,
            'message' => 'SMS notification preference updated successfully.',
            'data' => [
                'receiveSMS' => (bool) $user->profile->fresh()->receive_sms, // ensures boolean
            ],
        ]);
    }

    public function fees()
    { 
   


        return response()->json([
            'success' => true,
            'data'=> [ 'physical_card_fee' =>  (float) getSiteSettings("physical_card_creation_fee"),
            'ngn_card_creation_fee' => (float) getSiteSettings("naira_card_charge"),
            'usd_card_creation_fee' => (float) getSiteSettings("dollar_card_charge"),
            'usd_min_deposit' => 3, 
            'ngn_min_deposit' => 3500
            ]
            
        ]);
    }

}
