<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class VerifyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = User::findOrFail(Auth::user()->id);

        if ($user->account_activation != "1") {
            if (getSiteSettings("auto_account_activation") != "Yes"){
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Sorry Your Account is awaiting activation, contact admin or check back later..!"]);
            }

            Auth::logout();
            session()->flash('message','Sorry Your Account is awaiting activation, contact admin or check back later..!');
            session()->flash('type','danger');
            return redirect()->route('login');
        }
        }
        else if ($user->email_verify != "1") {
            if (getSiteSettings("email_verify") == "Yes"){
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Email address not verify"]);
            }
            return redirect()->route('email-verify');
        }
        }
        else if ($user->phone_verify != "1") {
            if (getSiteSettings("phone_verify") == "Yes"){
            if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
                return response(['success' => false, 'message' => "Phone number not verify"]);
            }
            return redirect()->route('phone-verify');
        }
        }
       elseif ($user->ban_status == "1") {
        if(isset($_SERVER['HTTP_ISMART'])  && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api")){
            return response(['success' => false, 'message' => "Sorry Your Account is Block Now Now..!"]);
        }
            Auth::logout();
            session()->flash('message','Sorry Your Account is Block Now Now..!');
            session()->flash('type','danger');
            return redirect()->route('login');
        }
        return $next($request);
    }
}
