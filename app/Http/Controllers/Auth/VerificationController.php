<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;

use App\Models\Profile;
use App\TraitsFolder\MailTrait;
use App\Models\User;
use App\Models\UserLogin;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use MailTrait;
    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    //protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('auth');
    }
    public function getEmailVerification()
    {
        if (Auth::user()->email_verify != 1){
            $data['page_title'] = "Email Verification";
            return view(enableMobile().'auth.email-verify',$data);
        }else{
            return redirect('user/dashboard');
        }
    }
    public function emailVerifySubmit(Request $request)
    {
        $this->validate($request,[
            'code' => 'required',
        ]);
        $user = Auth::user();
        if ($user->email_code == $request->code)
        {
            //$user = Auth::user();
            $user->email_verify = "1";
            $user->save();

            //send welcome mail
            try {
                $this->sendWelcomeMail($user->email,Auth::user());
            } catch (\Throwable $th) {
                //throw $th;
            }

            $user->profile;

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                //$user = User::whereId(auth()->user()->id)->with('profile')->firstOrFail();
                return response(['success' => true, 'message' => "Verification Successful", "user" => $user]);
            }

            return redirect()->route('UserDashboard');
        }else{

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Verification Code in Invalid"]);
            }

            session()->flash('message','Verification Code is Invalid');
            session()->flash('type','danger');
            return redirect()->route('email-verify');
        }
    }


    public function sendEmailOTP(Request $request)
    {
        $this->validate($request,[
            'email' => '',
            'nextScreen' => ''
        ]);

        $user = Auth::user();
        if ($user->email_time > Carbon::now()){
            $tt = Carbon::parse($user->email_time)->diffForHumans();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Please Try Again. After $tt."]);
            }

            session()->flash('message',"Please Try Again. After $tt.");
            session()->flash('type','danger');
            return redirect()->route('email-verify');
        }else{
            $email_code = strtoupper(Str::random(6));
            $text = $email_code;
            try {
                $this->sendCMail($user->email,$user->profile->username,'Verification OTP',$text);
            } catch (\Throwable $th) {

            }
            $useOwner = User::findOrFail($user->id);
            $useOwner->email_code = $email_code;
            $useOwner->email_time = Carbon::parse()->addMinutes(2);
            $useOwner->save();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => true, 'message' => "Verification OTP Sent To Your Email."]);
            }

            session()->flash('message',"Verification OTP Sent To Your Email Address.");
            session()->flash('type','success');
            return redirect()->route('email-verify');
        }
    }


    public function verifyEmailOTP(Request $request)
    {
        $this->validate($request,[
            'code' => 'required',
            'nextScreen' => ''
        ]);
        $user = Auth::user();
        if ($user->email_code == $request->code)
        {
            if(request('nextScreen') && request('nextScreen') == "TransactionPin"){
                $user->transaction_pin = null;
                $user->save();
            }

        $user = User::whereId(auth()->user()->id)->with('profile')->firstOrFail();

        return response(['success' => true, 'message' => "Verification Successful", "user" => $user]);

        }else{

                return response(['success' => false, 'message' => "Verification Code is Invalid"]);
        }
    }



    public function resendEmail(Request $request)
    {
        $user = Auth::user();
        if ($user->email_time > Carbon::now())
        {
            $tt = Carbon::parse($user->email_time)->diffForHumans();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Please Try Again. After $tt."]);
            }

            session()->flash('message',"Please Try Again. After $tt.");
            session()->flash('type','danger');
            return redirect()->route('email-verify');
        }else{
            $email_code = strtoupper(Str::random(6));
            $text = $email_code;
            try {
                $this->sendCMail($user->email,$user->profile->username,'Email verification',$text);
            } catch (\Throwable $th) {
                //throw $th;
            }
            $useOwner = User::findOrFail($user->id);
            $useOwner->email_code = $email_code;
            $useOwner->email_time = Carbon::parse()->addMinutes(5);
            $useOwner->save();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => true, 'message' => "New Email Verification Code Sent To Your Email Address."]);
            }

            session()->flash('message',"New Email Verification Code Sent To Your Email Address.");
            session()->flash('type','success');
            return redirect()->route('email-verify');
        }
    }
    public function getPhoneVerification()
    {
        if (Auth::user()->profile->phone_verify != 1){
            $data['page_title'] = "Phone Verification";
            return view(enableMobile().'auth.phone-verify',$data);
        }else{
            return redirect('user/dashboard');
        }
    }
    public function phoneVerifySubmit(Request $request)
    {
        $this->validate($request,[
            'code' => 'required',
        ]);
        $user = User::findOrFail(Auth::user()->id);
        if ($user->profile->phone_code == $request->code)
        {
            $useOwner = User::findOrFail($user->id);
            $useOwner->profile->phone_verify = "1";
            $useOwner->profile->save();
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                $user = User::whereId(auth()->user()->id)->with('profile')->firstOrFail();
                return response(['success' => true, 'message' => "Verification Successful", "user" => $user]);
            }
            return redirect('user/dashboard');
        }else{
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Verification Code in Invalid"]);
            }
            session()->flash('message','Verification Code in Invalid');
            session()->flash('type','danger');
            return redirect()->back();
        }
    }
    public function changePhone()
    {
        $user = User::findOrFail(Auth::user()->id);
        if ($user->profile->phone_time > Carbon::now())
        {
            $tt = Carbon::parse($user->profile->phone_time)->diffForHumans();
            session()->flash('message',"Please Try Again. After $tt.");
            session()->flash('type','danger');
            return redirect()->back();
        }
        if (Auth::user()->profile->phone_verify != 1){
            $data['page_title'] = "Change Verification Phone";
            return view(enableMobile().'auth.phone-change',$data);
        }else{
            return redirect('user/dashboard');
        }
    }

    public function resendPhone(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        if ($user->profile->phone_time > Carbon::now())
        {
            $tt = Carbon::parse($user->profile->phone_time)->diffForHumans();
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Please Try Again. After $tt."]);
            }
            session()->flash('message',"Please Try Again. After $tt.");
            session()->flash('type','danger');
            return redirect()->back();
        }else{
            $phone_code = strtoupper(Str::random(6));
            $txt = "Your Verification Code is: $phone_code";
            $txt = urlencode($txt);
            $to = $user->phone;
            $this->sendSms($to,$txt);
            $useOwner = User::findOrFail($user->id);
            $useOwner->profile->phone_code = $phone_code;
            $useOwner->profile->phone_time = Carbon::parse()->addMinutes(5);
            $useOwner->profile->save();

            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => true, 'message' => "New Phone Verification Code Send Your Phone Number."]);
            }

            session()->flash('message',"New Phone Verification Code Send Your Phone Number.");
            session()->flash('type',"success");
            return redirect()->back();
        }
    }
    public function submitChangePhone(Request $request)
    {
        $user = Auth::user();
        $this->validate($request,[
           'phone' => 'required|min:10|unique:profiles,phone,'.$user->profile->user_id
        ]);
        $useOwner = User::findOrFail(Auth::user()->id);
        $useOwner->profile->phone = $request->phone;
        $phone_code = strtoupper(Str::random(6));
        $txt = "Your Verification Code is: $phone_code";
        $txt = urlencode($txt);
        $to = $request->phone;
        $this->sendSms($to,$txt);
        $useOwner->profile->phone_code = $phone_code;
        $useOwner->profile->phone_time = Carbon::parse()->addMinutes(5);
        $useOwner->profile->save();

        if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
            return response(['success' => true, 'message' => "Your Phone Number Changes Successfully. Please verify Now."]);
        }
        session()->flash('message','Your Phone Number Changes Successfully. Please verify Now.');
        session()->flash('type',"success");
        return redirect()->route('phone-verify');
    }
}
