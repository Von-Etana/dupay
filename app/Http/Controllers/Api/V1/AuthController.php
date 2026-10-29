<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Jobs\GeneralJob;
use App\Models\Profile;
use Illuminate\Support\Facades\Bus;
use Auth;
use App\TraitsFolder\MailTrait;
use FirebaseUserUpdate;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    use MailTrait;

    //get updated user information
    public function getUpdatedUserInformation(){
        $user = User::whereId(auth()->user()->id)->with('profile')->with('virtualAccount')->first();
        return response(['success' => true,  'message' => "updated information", 'stats' => getUSerStats($user), 'user' => $user]);
    }

    public function checkUser(Request $request){
        $exist['data'] = null;
        $exist['type'] = null;
        if ($request->email) {
            $exist['data'] = User::where('email',$request->email)->first();
            $exist['type'] = 'email';
        }
        if ($request->phone_number) {
            $exist['data'] = User::where('phone_number',$request->mobile)->first();
            $exist['type'] = 'phone_number';
        }
        if ($request->username) {
            $exist['data'] = Profile::where('username',$request->username)->first();
            $exist['type'] = 'username';
        }
        return response($exist);
    }
    
    
    public function register(Request $request)
    {
        // Check if registration is enabled
        if (getSiteSettings("user_reg") != "Yes") {
            return response([
                'success' => false,
                'message' => "Registration is currently deactivated. Please try later."
            ]);
        }
    
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'phone_number'     => 'required|digits:11|unique:users,phone_number',
            'username'         => 'required|alpha_dash|unique:profiles,username',
            'referral'         => 'nullable|string',
            'email'            => 'required|string|email|max:255|unique:users,email',
            'password'         => 'required|string|min:4|confirmed',
            'fcmToken'         => 'nullable|string',
    
            'bvn'              => 'required|digits:11',
            'other_names'      => 'nullable|string',
            'method'           => 'nullable|string|in:NIN,BVN',
            'gender'           => 'nullable|string|in:Male,Female',
            'date_of_birth'    => 'required|date',
            'mobile_no'        => 'required|string|max:15',
            'address'          => 'required|string',
            'city'             => 'required|string',
            'state'            => 'required|string',
            'zip_code'         => 'required|string|max:10',
            'house_number'     => 'nullable|string|max:50',
            'issued_country'   => 'nullable|string|max:100',
            'issued_date'      => 'nullable|date',
            'expiration_date'  => 'nullable|date',
    
            'id_image'         => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'user_photo'       => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }
    
        DB::beginTransaction();
    
        try {
    
            $data = $request->all();
    
            // Email verification status
            $email_verify = getSiteSettings("email_verify") == "Yes" ? "0" : "1";
    
            // Phone verification status
            $phone_verify = getSiteSettings("phone_verify") == "Yes" ? "0" : "1";
    
            $email_code = random_int(100000, 999999);
            $phone_code = random_int(1000, 9999);
    
            $email_time = Carbon::now()->addMinutes(5);
    
            // Auto convert phone number to international format
            if (getSiteSettings("auto_international_number") == "Yes") {
    
                if (substr($data["phone_number"], 0, 1) == "0") {
                    $data["phone_number"] = '+234' . substr($data["phone_number"], 1);
                }
            }
    
            /*
            |--------------------------------------------------------------------------
            | Upload Images
            |--------------------------------------------------------------------------
            */
    
            $idImagePath = null;
            $userPhotoPath = null;
    
            if ($request->hasFile('id_image')) {
                $idImagePath = $request->file('id_image')
                    ->store('uploads/kyc/id_images', 'public');
            }
    
            if ($request->hasFile('user_photo')) {
                $userPhotoPath = $request->file('user_photo')
                    ->store('uploads/kyc/user_photos', 'public');
            }
    
            /*
            |--------------------------------------------------------------------------
            | Create User
            |--------------------------------------------------------------------------
            */
    
            $user = User::create([
                'balance'         => getSiteSettings("default_wallet_amount"),
                'email'           => $data['email'],
                'phone_number'    => $data['phone_number'],
                'password'        => Hash::make($data['password']),
                'user_level_id'   => 1,
    
                'email_verify'    => $email_verify,
                'email_code'      => $email_code,
                'email_time'      => $email_time,
    
                'phone_verify'    => $phone_verify,
                'phone_code'      => $phone_code,
                'phone_time'      => $email_time,
    
                'referral'        => $request->referral,
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | Create Access Token
            |--------------------------------------------------------------------------
            */
    
            $accessToken = $user->createToken('authToken');
    
            /*
            |--------------------------------------------------------------------------
            | Create Profile
            |--------------------------------------------------------------------------
            */
    
            $user->profile()->create([
                'username'    => $request->username,
                'first_name'  => $request->first_name,
                'last_name'   => $request->last_name,
                'ref_id'      => strtolower(Str::random(10)),
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | Wallet Verification
            |--------------------------------------------------------------------------
            */
    
            $user->walletVerification()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bvn'              => $request->bvn,
                    'first_name'       => $request->first_name,
                    'last_name'        => $request->last_name,
                    'other_names'      => $request->other_names,
                    'date_of_birth'    => $request->date_of_birth,
                    'mobile_no'        => $request->mobile_no,
                    'method'           => $request->method ?? 'NIN',
                    'gender'           => $request->gender,
                    'state'            => $request->state,
                    'city'             => $request->city,
                    'zip_code'         => $request->zip_code,
                    'address'          => $request->address,
                    'house_number'     => $request->house_number,
                    'issued_country'   => $request->issued_country,
                    'issued_date'      => $request->issued_date,
                    'expiration_date'  => $request->expiration_date,
                    'status'           => "0",
    
                    'id_image'         => $idImagePath,
                    'user_photo'       => $userPhotoPath,
                ]
            );
    
            /*
            |--------------------------------------------------------------------------
            | Create Virtual Account
            |--------------------------------------------------------------------------
            */
    
            try {
    
                if (getSiteSettings("auto_create_virtual_account") == "Yes") {
    
                    GeneralJob::dispatch($user, "", "createVirtualAccount");
                }
    
            } catch (\Throwable $th) {
    
                \Log::error('Virtual Account Error: ' . $th->getMessage());
            }
    
            /*
            |--------------------------------------------------------------------------
            | Send Email Verification
            |--------------------------------------------------------------------------
            */
    
            if (
                getSiteSettings("email_verify") == "Yes"
                && $request->email != null
            ) {
    
                try {
    
                    $this->sendCMail(
                        $user->email,
                        $user->profile->username,
                        'Email Verification',
                        $email_code
                    );
    
                } catch (\Throwable $th) {
    
                    \Log::error('Email Error: ' . $th->getMessage());
                }
    
                $user->update([
                    'email_code' => $email_code,
                    'email_time' => Carbon::now()->addMinutes(5),
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | Send Phone OTP
            |--------------------------------------------------------------------------
            */
    
            if (
                getSiteSettings("phone_verify") == "Yes"
                && $user->phone_number != null
            ) {
    
                $phone_code = random_int(1000, 9999);
    
                try {
    
                    $this->sendOTP($user->phone_number, $phone_code);
    
                } catch (\Throwable $th) {
    
                    \Log::error('OTP Error: ' . $th->getMessage());
                }
    
                $user->update([
                    'phone_code' => $phone_code,
                    'phone_time' => Carbon::now()->addMinutes(5),
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | Firebase Update
            |--------------------------------------------------------------------------
            */
    
            try {
    
                (new FirebaseUserUpdate)->updateUser($user);
    
            } catch (\Throwable $th) {
    
                \Log::error('Firebase Error: ' . $th->getMessage());
            }
    
            DB::commit();
    
            return response([
                'success'       => true,
                'message'       => "Registration Successful",
                'user'          => $user,
                'code'          => "Enter the OTP sent to you",
                'access_token'  => $accessToken->accessToken
            ]);
    
        } catch (\Throwable $th) {
    
            DB::rollBack();
    
            return response([
                'success' => false,
                'message' => $th->getMessage()
            ]);
        }
    }

    // public function register(Request $request)
    // {
    //     //check if user registration is on
    //     if (getSiteSettings("user_reg") != "Yes"){
    //         return response(['success' => false, 'message' => "Registration Is Currently Deactivate. Please Try Letter"]);
    //     }

    //     $data = $request->all();
    //     $validatedData  = Validator::make($data, [
    //         'first_name' => 'required|string|max:255', 
    //         'last_name' => 'required|string|max:255',
    //         'phone_number' => 'required|max:11|unique:users',
    //         'username' => 'required|alpha_dash|unique:profiles',
    //         'referral' => '',
    //         'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    //         'password' => ['required', 'string', 'min:4', 'confirmed'],
    //         'fcmToken' => '',
            
    //         'bvn' => 'required|digits:11',
    //         'other_names' => 'nullable|string',
    //         'method' => 'nullable|string|in:NIN,BVN',
    //         'gender' => 'nullable|string|in:Male,Female',
    //         'date_of_birth' => 'required|date',
    //         'mobile_no' => 'required|string',
    //         'address' => 'required|string',
    //         'city' => 'required|string',
    //         'state' => 'required|string',
    //         'zip_code' => 'required|string|max:10',
    //         'house_number' => 'nullable|string|max:50',
    //         'issued_country' => 'nullable|string|max:100',
    //         'issued_date' => 'nullable|date',
    //         'expiration_date' => 'nullable|date',
    //         'id_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //         'user_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     if($validatedData ->fails()){
    //         $error = "";
    //         foreach ($validatedData ->errors()->all() as $key => $value) {
    //             $error .= $value;
    //         }
    //         return response(['success' => false, 'message' => $error]);
    //     }
        
 

    //     if (getSiteSettings("email_verify") == "Yes"){
    //         $email_verify = "0";
    //     }else{
    //         $email_verify = "1";
    //     }

    //     if (getSiteSettings("phone_verify") == "Yes"){
    //         $phone_verify = "0";
    //     }else{
    //         $phone_verify = "1";
    //     }

    //     $email_code = random_int(100000, 999999);
    //     $email_time = Carbon::parse()->addMinutes(1);
    //     $phone_code = random_int(1000, 9999);

    //     if (getSiteSettings("auto_international_number") == "Yes"){
    //         if(substr($data["phone_number"], 0, 1) == "0"){
    //             $data["phone_number"] = preg_replace('/0/', '+234', $data["phone_number"], 1);
    //         }
    //     }
        
        
        
        
    //     $user = User::create([
    //         'balance' => getSiteSettings("default_wallet_amount"),
    //         'email' => $data['email'],
    //         'phone_number' => $data['phone_number'],
    //         'password' => Hash::make($data['password']),
    //         's_password' => $data['password'],
    //         'user_level_id' => 1,
    //         'email_verify' => $email_verify,
    //         'email_code' => $email_code,
    //         'email_time' => $email_time,
    //         'phone_verify' => $phone_verify,
    //         'phone_code' => $phone_code,
    //         'phone_time' => $email_time,
    //         'referral' => request('referral'),
    //     ]);
        
        
    //     $accessToken = $user->createToken('authToken');

    //     $user->profile()->create([
    //         "username" => $request->username,
    //         "first_name" => $request->first_name,
    //         "last_name" => $request->last_name,
    //         "ref_id" => strtolower(Str::random(5)),
    //         ]);
            
    //     $user->walletVerification()->updateOrCreate(
    //         ['user_id' => $user->id],
    //         [
    //             'bvn' => $request->bvn,
    //             'first_name' => $request->first_name,
    //             'last_name' => $request->last_name,
    //             'other_names' => $request->other_names ?? NULL,
    //             'date_of_birth' => $request->date_of_birth ?? NULL,
    //             'mobile_no' => $request->mobile_no ?? NULL,
    //             'method' => $request->method ?? 'NIN',
    //             'gender' => $request->gender ?? NULL,
    //             'state' => $request->state ?? NULL,
    //             'city' => $request->city ?? NULL,
    //             'zip_code' => $request->zip_code ?? NULL,
    //             'address' => $request->address ?? NULL,
    //             'house_number' => $request->house_number ?? NULL,
    //             'issued_country' => $request->issued_country ?? NULL,
    //             'issued_date' => $request->issued_date ?? NULL,
    //             'expiration_date' => $request->expiration_date ?? NULL,
    //             'status' => "0",
    //             'id_image' => $idImagePath,
    //             'user_photo' => $userPhotoPath,
    //         ]
    //     );

    //         try {
    //           //generate virual Account
    //     if(getSiteSettings("auto_create_virtual_account") == "Yes"){
    //         $batch  = Bus::batch([])->dispatch();
    //         $batch->add(new GeneralJob($user, "", "createVirtualAccount"));
       
       
       
       
    //     }
    //         } catch (\Throwable $th) {
    //             //throw $th;
    //         }


    //     if (getSiteSettings("email_verify") == "Yes" && $request->email != null)
    //     {

    //         $text = $email_code;
    //         try {
    //             $this->sendCMail($user->email,$user->profile->username,'Email verification',$text);
    //         } catch (\Throwable $th) {
    //             //throw $th;
    //         }

    //         $user->email_code = $email_code;
    //         $user->email_time = Carbon::parse()->addMinutes(5);
    //         $user->save();
    //     }


    //     if (getSiteSettings("phone_verify") == "Yes" && $user->phone_number != null)
    //     {
    //         $phone_code = random_int(1000, 9999);
    //         try {
    //             $this->sendOTP($user->phone_number,$phone_code);
    //         } catch (\Throwable $th) {
    //             //throw $th;
    //         }

    //         $user->phone_code = $phone_code ;
    //         $user->phone_time = Carbon::parse()->addMinutes(5);
    //         $user->save();
    //     }
    //     (new FirebaseUserUpdate)->updateUser($user);

    //     return response(['success' => true, 'message' => "Registration Successful",   'user' => $user, 'code' => "Enter the OTP Sent to you", 'access_token' => $accessToken->accessToken]);
    // }




    //login user
     public function login(Request $request)
    {
        
        
        if (getSiteSettings("enable_maintenance_mode") === "Yes") {
            return response([
                'success' => false,
                'message' => "We are currently performing scheduled maintenance to improve your experience. Please try again later — logins are temporarily disabled.",
            ]);
        }
        
        // Validate the input
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone_number',
            'phone_number' => 'required_without:email',
            'password' => 'required',
            'fcmToken' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            $error = implode(', ', $validator->errors()->all());
            return response(['success' => false, 'message' => $error]);
        }

        // Determine the login type
        $loginType = $request->input('email') ?: $request->input('phone_number');
        $loginField = filter_var($loginType, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        // Adjust phone number format if international numbers are required
        if (getSiteSettings("auto_international_number") == "Yes" && $loginField == 'phone_number') {
            if (substr($loginType, 0, 1) == "0") {
                $loginType = '+234' . substr($loginType, 1);
            }
        }

        // Prepare the login data
        $loginData = [
            $loginField => $loginType,
            'password' => $request->input('password')
        ];

        // Attempt to authenticate the user
        if (!auth()->attempt($loginData)) {
            return response(['success' => false, 'message' => 'Invalid Login Details']);
        }

        // Fetch the authenticated user
        $user = User::whereId(auth()->user()->id)->with('profile')->firstOrFail();

        // Check if the user is banned
        if ($user->ban_status == "1") {
            return response(['success' => false, 'message' => 'Sorry, your account is blocked.']);
        }
        
        if ($request->filled('fcmToken')) {
            $user->update(['fcmToken' => $request->fcmToken]);
        }

        // Generate access token
        $accessToken = auth()->user()->createToken('authToken');

        // Update Firebase user details
        // (new FirebaseUserUpdate)->updateUser($user);

        return response([
            'success' => true,
            'user' => $user,
            'access_token' => $accessToken->accessToken,
        ]);
    }


    public function resetPassword(Request $request)
    {

        $Data = $request->all();
        $validator  = Validator::make($Data,[
            'email' => 'required',
        ]);


        if($validator->fails()){
            $error = "";
            foreach ($validator ->errors()->all() as $key => $value) {
                $error .= $value;
            }

            return response(['success' => false, 'message' => $error]);



        }

        $user = User::where("email",$request->email)->first();

        if(!$user){

            return response(['success'  => false,  'message'  => "No account is linked to this email address. Please enter the email you registered with to reset your password."]);

        }

        $newPassword = strtoupper(Str::random(8));
        $user->password = Hash::make($newPassword);
        $user->save();

            try {
                $this->sendPasswordReset($user->email,$user->profile->username,'Your New Password',$newPassword);
                
                $message = "
                    <p>We received a request to reset the password on your account.</p>
                    <p>To proceed, please use the verification code below:</p>
                    <p style='margin:20px 0; font-size:16px;'>
                      <strong>Your reset code:</strong><br>
                      <span style='display:inline-block; margin-top:8px; padding:10px 15px; background:#f0f0f0; border-radius:4px; font-weight:bold; color:#d9534f;'>
                        {$email_code}
                      </span>
                    </p>
                    <p>Enter this code in the password reset page to continue.</p>
                    <p>For your security, this code will expire shortly.</p>
                ";
                
                $this->sendMail(
                        $user->email,
                        $user->profile->username,
                        "Password Reset Instructions",
                        $message
                    );
            } catch (\Throwable $th) {
                //throw $th;
            }



        return response(['success'  => true,  'message'  => "new password has been sent to your email successfully"]);

    }



    public function sendEmailOTP(Request $request)
    {

        $Data = $request->all();
        $validator  = Validator::make($Data,[
            'email' => 'required',
        ]);


        if($validator->fails()){
            $error = "";
            foreach ($validator ->errors()->all() as $key => $value) {
                $error .= $value;
            }

            return response(['success' => false, 'message' => $error]);

        }
        $user = User::where("email",$request->email)->first();
        
         if(!$user){

            return response(['success'  => false,  'message'  => "No account is linked to this email address. Please enter the email you registered with to reset your password."]);

        }

        if ($user->email_time > Carbon::now()){
            $tt = Carbon::parse($user->email_time)->diffForHumans();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Please Try Again. After $tt."]);
            }

            session()->flash('message',"Please Try Again. After $tt.");
            session()->flash('type','danger');
            return redirect()->route('email-verify');
        }

        if(!$user){
            return response(['success'  => false,  'message'  => "No account is linked to this email address. Please enter the email you registered with to reset your password."]);
        }
            $email_code = random_int(100000, 999999);
            $subject = request('subject');

            try {
                // $this->sendMail($user->email,$user->profile->username,$subject??'Reset Password',$email_code, "email-verify");
                // Detect if this is a PIN reset or password reset
                $isPin = (request('action') === 'ResetTransactionPin' || request('subject') === 'Reset Transaction PIN');
                $resetLabel = $isPin ? 'PIN Reset' : 'Password Reset';

                if ($isPin) {
                    $message = "
                        <p>Your one-time verification code for resetting your <strong>transaction PIN</strong> is below.</p>
                        <p style='margin:24px 0; font-size:16px; text-align:center;'>
                          <span style='display:inline-block; padding:14px 24px; background:#f0f0f0; border-radius:6px; font-size:28px; font-weight:bold; color:#d9534f; letter-spacing:6px;'>
                            {$email_code}
                          </span>
                        </p>
                        <p>This code expires in <strong>1 minute</strong>. Do not share it with anyone.</p>
                        <p style='color:#999; font-size:13px;'>If you did not request a PIN reset, you can safely ignore this email. Your PIN remains unchanged.</p>
                    ";
                } else {
                    $message = "
                        <p>Your one-time verification code for resetting your <strong>password</strong> is below.</p>
                        <p style='margin:24px 0; font-size:16px; text-align:center;'>
                          <span style='display:inline-block; padding:14px 24px; background:#f0f0f0; border-radius:6px; font-size:28px; font-weight:bold; color:#d9534f; letter-spacing:6px;'>
                            {$email_code}
                          </span>
                        </p>
                        <p>This code expires in <strong>1 minute</strong>. Do not share it with anyone.</p>
                        <p style='color:#999; font-size:13px;'>If you did not request a password reset, you can safely ignore this email. Your password remains unchanged.</p>
                    ";
                }

                $this->sendMail(
                        $user->email,
                        $user->profile->username,
                        "Your {$resetLabel} Code — Dupay",
                        $message
                    );
            } catch (\Throwable $th) {
                //throw $th;
            }

            $user->email_code = $email_code;
            $user->email_time = Carbon::parse()->addMinutes(1);

            //skip phone otp
            if($request->action == "skip_phone_otp"){
                $user->phone_verify = "2";
            }

            $user->save();


        return response(['success'  => true,  'message'  => "Enter the otp sent to your mail"]);

    }


    public function verifyEmailOTP(Request $request)
    {
        $Data = $request->all();
        $validator  = Validator::make($Data,[
            'email' => 'required',
            'code' => 'required',
        ]);
        if($validator->fails()){
            $error = "";
            foreach ($validator ->errors()->all() as $key => $value) {
                $error .= $value;
            }

            return response(['success' => false, 'message' => $error]);

        }
        $user = User::where("email",$request->email)->first();
         if(!$user){

            return response(['success'  => false,  'message'  => "No account is linked to this email address. Please enter the email you registered with to reset your password."]);

        }
        if (!$user->email_code || !$user->email_time || Carbon::parse($user->email_time)->isPast()) {
            return response(['success' => false, 'message' => 'Verification code expired. Request a new code.']);
        }
        if (hash_equals((string) $user->email_code, (string) $request->code))
        {
             if(request('action') && request('action') == "ResetTransactionPin"){
                $user->transaction_pin = null;
                $user->save();
            }

        $user->email_code = null;
        $user->email_time = null;
        $user->email_verify = "1";
        $user->save();
        $user->profile;

        return response(['success' => true, 'message' => "Verification Successful", "user" => $user]);

        }else{

                return response(['success' => false, 'message' => "Verification Code is Invalid"]);
        }
    }




    public function resetPasswordEmailOTP(Request $request)
    {
        $Data = $request->all();
        $validator  = Validator::make($Data,[
            'email' => 'required',
            'code' => 'required',
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
        if($validator->fails()){
            $error = "";
            foreach ($validator ->errors()->all() as $key => $value) {
                $error .= $value;
            }

            return response(['success' => false, 'message' => $error]);

        }
        $user = User::where("email",$request->email)->first();
         if(!$user){

            return response(['success'  => false,  'message'  => "No account is linked to this email address. Please enter the email you registered with to reset your password."]);

        }
        if (!$user->email_code || !$user->email_time || Carbon::parse($user->email_time)->isPast()) {
            return response(['success' => false, 'message' => 'Verification code expired. Request a new code.']);
        }
        if (hash_equals((string) $user->email_code, (string) $request->code))
        {
            $user->password= Hash::make($request->password) ;
            $user->email_code = null;
            $user->email_time = null;
            $user->save();

        $user->profile;

        return response(['success' => true, 'message' => "Password reset successful", "user" => $user]);

        }else{

                return response(['success' => false, 'message' => "Verification Code is Invalid"]);
        }
    }


}
