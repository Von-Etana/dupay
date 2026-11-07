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
        $authUser = Auth::user();
        if (!$authUser) {
            return $next($request);
        }

        $user = User::find($authUser->id);
        if (!$user) {
            return response(['success' => false, 'message' => "User not found"], 401);
        }

        $isApi = $request->expectsJson()
            || $request->is('api/*')
            || (isset($_SERVER['HTTP_ISMART']) && ($_SERVER['HTTP_ISMART'] == 'app' || $_SERVER['HTTP_ISMART'] == "api"));

        // 1. Check account blocked / banned status
        if ($user->ban_status == "1" || \App\Services\UserBlockService::isBlocked($user)) {
            if ($isApi) {
                return response(['success' => false, 'message' => "Sorry Your Account is Block Now Now..!"], 403);
            }
            Auth::logout();
            session()->flash('message', 'Sorry Your Account is Block Now Now..!');
            session()->flash('type', 'danger');
            return redirect()->route('login');
        }

        // 2. Check admin account activation
        if ($user->account_activation != "1") {
            if (getSiteSettings("auto_account_activation") != "Yes") {
                if ($isApi) {
                    return response(['success' => false, 'message' => "Sorry Your Account is awaiting activation, contact admin or check back later..!"], 403);
                }
                Auth::logout();
                session()->flash('message', 'Sorry Your Account is awaiting activation, contact admin or check back later..!');
                session()->flash('type', 'danger');
                return redirect()->route('login');
            }
        }

        // 3. Check email OTP verification (must be independent of account activation)
        if ($user->email_verify != "1") {
            if (getSiteSettings("email_verify") == "Yes") {
                if ($isApi) {
                    return response(['success' => false, 'code' => 'email_unverified', 'message' => "Email address not verify"], 403);
                }
                return redirect()->route('email-verify');
            }
        }

        // 4. Check phone OTP verification (must be independent of previous checks)
        if ($user->phone_verify != "1") {
            if (getSiteSettings("phone_verify") == "Yes") {
                if ($isApi) {
                    return response(['success' => false, 'code' => 'phone_unverified', 'message' => "Phone number not verify"], 403);
                }
                return redirect()->route('phone-verify');
            }
        }

        return $next($request);
    }
}
