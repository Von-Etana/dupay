<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin2FaVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        // If not logged in as admin, let subsequent middleware handle redirect to login
        if (! $admin) {
            return $next($request);
        }

        // Email OTP is mandatory for every admin login.
        $isVerified = (bool) session('admin_2fa_verified');

        if (! $isVerified) {
                // Allowed routes during 2FA challenge
                $exemptRouteNames = [
                    'admin.2fa.challenge',
                    'admin.2fa.verify',
                    'admin.2fa.resend',
                    'AdminLogout',
                    'adminAuth.Login',
                ];

                $currentRouteName = $request->route() ? $request->route()->getName() : null;

                if (in_array($currentRouteName, $exemptRouteNames, true)) {
                    return $next($request);
                }

                return redirect()->route('admin.2fa.challenge');
        }

        return $next($request);
    }
}
