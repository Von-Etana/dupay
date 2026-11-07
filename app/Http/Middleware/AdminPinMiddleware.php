<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminPinMiddleware
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
        // admin guard check
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        // check pin session
        $pinVerifiedAt = session('admin_pin_verified_at');

        // if no pin session
        if (!$pinVerifiedAt) {
            return redirect()->route('admin.pin.form');
        }

        // expire after X minutes (example: 10 mins)
        $expireMinutes = 40;

        if (Carbon::parse($pinVerifiedAt)->addMinutes($expireMinutes)->isPast()) {
            session()->forget('admin_pin_verified_at');

            return redirect()->route('admin.pin.form')
                ->with('error', 'Pin session expired. Enter PIN again.');
        }

        return $next($request);
    }
}
