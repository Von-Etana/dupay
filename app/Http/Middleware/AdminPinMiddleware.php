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
            return redirect()->route('adminAuth.Login');
        }

        // check pin session
        $adminId = (int) auth()->guard('admin')->id();
        $pinVerifiedAdminId = (int) session('admin_pin_verified_admin_id');
        $pinVerifiedAt = session('admin_pin_verified_at');
        if ($pinVerifiedAdminId !== $adminId) {
            session()->forget(['admin_pin_verified_at', 'admin_pin_verified_admin_id']);
            return redirect()->route('admin.pin.form');
        }

        // if no pin session
        if (!$pinVerifiedAt) {
            return redirect()->route('admin.pin.form');
        }

        // expire after X minutes (example: 10 mins)
        $expireMinutes = 40;

        if (Carbon::parse($pinVerifiedAt)->addMinutes($expireMinutes)->isPast()) {
            session()->forget(['admin_pin_verified_at', 'admin_pin_verified_admin_id']);

            return redirect()->route('admin.pin.form')
                ->with('error', 'Pin session expired. Enter PIN again.');
        }

        return $next($request);
    }
}
