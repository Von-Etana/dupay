<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Admin;
use Illuminate\Support\Facades\Session;

class PinController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required'
        ]);

        $admin = Auth::guard('admin')->user();

        if (!$admin || !Hash::check($request->pin, (string) $admin->pin)) {
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

        if (!$admin || !Hash::check($request->pin, (string) $admin->pin)) {
            session()->flash('message', 'Invalid PIN');
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return back();
        }

        // store verification timestamp
        session([
            'admin_pin_verified_at' => Carbon::now()
        ]);

        return redirect()->intended('/adminoffice');
    }
}
