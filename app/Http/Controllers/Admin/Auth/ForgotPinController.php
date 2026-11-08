<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Admin;

class ForgotPinController extends Controller
{
    public function requestForm()
    {
        return view('admin.auth.forgot-pin');
    }
    
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $admin = Admin::where('email',$request->email)->first();

        if(!$admin){
            return back()->with('error','Admin not found');
        }

        $token = Str::random(64);

        DB::table('admin_pin_resets')->updateOrInsert(
            ['email' => $admin->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        $link = route('admin.pin.reset.form', $token);

        // simple mail example
        Mail::raw("Reset your admin PIN here: ".$link, function($mail) use ($admin){
            $mail->to($admin->email)
                 ->subject('Admin PIN Reset');
        });

        return back()->with('success','Reset link sent');
    }

    public function resetForm($token)
    {
        return view('admin.auth.reset-pin',compact('token'));
    }

    public function resetPin(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'pin'   => 'required|min:4|confirmed'
        ]);

        $record = DB::table('admin_pin_resets')
            ->where('token',$request->token)
            ->first();

        if(!$record){
            return back()->with('error','Invalid token');
        }

        $admin = Admin::where('email',$record->email)->first();

        if(!$admin){
            return back()->with('error','Admin not found');
        }

        // update pin
        $admin->pin = Hash::make($request->pin);
        $admin->s_pin = null;
        $admin->save();

        DB::table('admin_pin_resets')->where('email',$admin->email)->delete();

        return redirect()->route('adminAuth.Login')
            ->with('success','PIN reset successful');
    }
}
