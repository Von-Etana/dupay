<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Admin;
use Illuminate\Support\Facades\Session;
use App\Services\SecurityAlertService;

class PinController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required'
        ]);

        $admin = Auth::guard('admin')->user();

        if (!$admin || !Hash::check($request->pin, (string) $admin->pin)) {
            SecurityAlertService::record(
                'admin_pin_failed',
                'high',
                'Failed admin PIN verification',
                'An admin PIN verification attempt failed.',
                $admin?->id,
                $request->ip()
            );
            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }
    
    public function showForm()
    {
        return view('admin.auth.pin');
    }
    
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required'
        ]);

        $admin = auth()->guard('admin')->user();

        $throttleKey = 'admin_pin_attempts_' . ($admin?->id ?? $request->ip());
        $attempts = (int) Cache::get($throttleKey, 0);
        if ($attempts >= 5) {
            SecurityAlertService::record('admin_pin_locked', 'critical', 'Admin PIN temporarily locked', 'Too many failed admin PIN attempts.', $admin?->id, $request->ip());
            return back()->with('error', 'Too many incorrect PIN attempts. Please wait 5 minutes.');
        }

        if (!$admin || !Hash::check($request->pin, (string) $admin->pin)) {
            Cache::put($throttleKey, $attempts + 1, now()->addMinutes(5));
            session()->flash('message', 'Invalid PIN');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        Cache::forget($throttleKey);

        // store verification timestamp
        session([
            'admin_pin_verified_at' => Carbon::now(),
            'admin_pin_verified_admin_id' => $admin->id,
        ]);

        addSiteActitvity($admin, [
            'activity' => 'Admin PIN verified',
            'actions' => 'pin_verify',
            'module' => 'Auth',
            'user_type' => '2',
        ]);

        return redirect()->intended('/adminoffice');
    }
}
