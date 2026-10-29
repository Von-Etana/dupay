<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\GeneralJob;
use App\Providers\RouteServiceProvider;
use App\TraitsFolder\MailTrait;
use App\Models\User;
use Carbon\Carbon;
use FirebaseUserUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Bus;


class RegisterController extends Controller
{
    use MailTrait;
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;
    protected $redirectTo = '/user';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    //check if registration is still allow
    public function showRegistrationForm()
    {

        $id = request()->input('referral');
        if (getSiteSettings("user_reg") != "Yes"){
            session()->flash('message','Registration Is Currently Deactivate. Please Try Letter.');
            session()->flash('type','danger');
            return redirect()->route('login');
        }
        $data['page_title'] = "Register";
        $data['referral'] = $id;
            return view(enableMobile().'auth.register',$data);
    }


    public function showreferralForm($id)
    {

        if (getSiteSettings("user_reg") != "Yes"){
            session()->flash('message','Registration Is Currently Deactivate. Please Try Letter.');
            session()->flash('type','danger');
            return redirect()->route('login');
        }
        $data['page_title'] = "Register";
        $data['referral'] = $id;
        return view(enableMobile().'auth.register',$data);
    }

    public function showLoginForm($id)
    {

        if (getSiteSettings("user_reg") != "Yes"){
            session()->flash('message','Registration Is Currently Deactivate. Please Try Letter.');
            session()->flash('type','danger');
            return redirect()->route('login');
        }
        $data['page_title'] = "Register";
        $data['referral'] = $id;
        return view(enableMobile().'auth.register',$data);
    }



    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|max:11|unique:users',
            'username' => 'required|alpha_dash|unique:profiles',
            'referral' => '',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {

        if (getSiteSettings("auto_account_activation") == "Yes"){
            $account_activation = "1";
        }else{
            $account_activation = "0";
        }


        if (getSiteSettings("email_verify") == "Yes"){
            $email_verify = "0";
        }else{
            $email_verify = "1";
        }

        if (getSiteSettings("phone_verify") == "Yes"){
            $phone_verify = "0";
        }else{
            $phone_verify = "1";
        }

        $email_code = strtoupper(Str::random(6));
        $email_time = Carbon::parse()->addMinutes(5);
        $phone_code = random_int(1000, 9999);

        if (getSiteSettings("auto_international_number") == "Yes"){
        if(substr($data["phone_number"], 0, 1) == "0"){
            $data["phone_number"] = preg_replace('/0/', '+234', $data["phone_number"], 1);
        }
        }

        $user = User::create([
            'balance' => getSiteSettings("default_wallet_amount"),
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'user_level_id' => 1,
            'account_activation' => $account_activation,
            'email_verify' => $email_verify,
            'email_code' => $email_code,
            'email_time' => $email_time,
            'phone_verify' => $phone_verify,
            'phone_code' => $phone_code,
            'phone_time' => $email_time,
            'referral' => $data['referral'],
        ]);
        $accessToken = $user->createToken('authToken');

        $user->profile()->create([
            'username' => $data['username'],
            "first_name" => $data['first_name'],
            "last_name" => $data['last_name'],
            "ref_id" => strtolower(Str::random(5))
            ]);


    return $user;
    }

    protected function registered(Request $request, $user)
    {

        //generate virual Account
        /*if(getSiteSettings("auto_create_virtual_account") == "Yes"){
            generateMonnifyVirtualAccount($user->id);
        }*/

         try {
               //generate virual Account
        if(getSiteSettings("auto_create_virtual_account") == "Yes"){
            $batch  = Bus::batch([])->dispatch();
            $batch->add(new GeneralJob($user, "", "createVirtualAccount"));
        }
            } catch (\Throwable $th) {
                //throw $th;
            }

        // if ($user->referral != null && getSiteSettings("enable_referral_bonus") == "Yes" && getSiteSettings("email_verify") != "Yes"){
        //     //check referral
        //    $refarral =  Profile::whereRefId($user->referral)->orWhere("username",$user->referral)->first();
        //    if($refarral){
        //     $refUser = User::where("id",$refarral->user_id)->first();


        //     $ip = NULL; $deep_detect = TRUE;

        // if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        //     $ip = $_SERVER["REMOTE_ADDR"];
        //     if ($deep_detect) {
        //         if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
        //             $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        //         if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
        //             $ip = $_SERVER['HTTP_CLIENT_IP'];
        //     }
        // }

        //     $checkUserIp = UserLogin::where("user_ip", $ip)->first();

        //     if($refUser && !$checkUserIp){
        //     $bonus = getSiteSettings("referral_bonus");
        //     $trx = strtoupper(Str::random(20));
        //     $ul['wallet_id'] = "3";
        //     $ul['user_level_id'] = $refUser->user_level_id;
        //     $ul['service'] = "System";
        //     $ul['service_type'] = "Referral Commission";
        //     $ul['description'] = "₦$bonus Commission For Referring ".$user->profile->username;
        //     $ul['amount'] = $bonus;
        //     $ul['phone_number'] = $refUser->phone_number;
        //     $ul['type'] = "1";
        //     $ul['initial_balance'] = $refUser->profile->commission ?? 0.0;
        //     $ul['final_balance'] = $refUser->profile->commission ?? 0.0 + $bonus;
        //     $ul['reference'] = $trx;
        //     $ul['status'] = "success";
        //     $refUser->transaction()->create($ul);

        //     if (getSiteSettings("email_notify") == "Yes"){
        //         $text = "Congratulations!!! you just recieve ₦$bonus Commission For Referring ".$user->profile->username." <br> Reference ID Is : #$trx";
        //         try {
        //             $this->sendMail($refUser->email,$refUser->profile->username,'Referral Commission',$text);
        //         } catch (\Throwable $th) {
        //             //throw $th;
        //         }
        //     }
        //     if (getSiteSettings("phone_notify") == "Yes"){
        //         $text = $bonus." Commission For Referring ".$user->profile->username." <br> Reference ID Is : #$trx";
        //         try {
        //             $this->sendSms($refUser->phone_number,$text);
        //         } catch (\Throwable $th) {
        //             //throw $th;
        //         }
        //     }

        //     //update the user commission balance
        //     $refUser->profile->commission = $refUser->profile->commission + $bonus;
        //     $refUser->profile->save();
        // }
        // }}


        if (getSiteSettings("email_verify") == "Yes" && $user->email != null)
        {
            $email_code = strtoupper(Str::random(6));
            $text = $email_code;
            try {
                $this->sendCMail($user->email,$user->profile->username,'Email verification',$text);
            } catch (\Throwable $th) {
                //throw $th;
            }

            $user->email_code = $email_code;
            $user->email_time = Carbon::parse()->addMinutes(5);
            $user->save();
        }


        if (getSiteSettings("phone_verify") == "Yes" && $user->phone_number != null)
        {
            $phone_code = random_int(1000, 9999);
            try {
                $this->sendOTP($user->phone_number,$phone_code);
            } catch (\Throwable $th) {
                //throw $th;
            }

            $user->phone_code = $phone_code ;
            $user->phone_time = Carbon::parse()->addMinutes(5);
            $user->save();
        }

        (new FirebaseUserUpdate)->updateUser($user);
    }
}
