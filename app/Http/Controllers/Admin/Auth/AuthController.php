<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetMailRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{


    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }
     /**
    * show login form
    *
    * @access public
    */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    protected function guard()
    {

        return Auth::guard('admin');
    }



    /**
    * submit login form
    * @request $request
    * @access public
    */
    // public function login(LoginRequest $request)
    // {

    //     $user=Admin::where('email',$request['email'])->first();
    //     //check if email exist
    //     if(isset($user)&&Hash::check($request['password'],$user['password']))
    //     {
    //         $remember=($request->has('remeber'))?true:false;

    //         Auth::guard('admin')->login($user,$remember);

    //         Auth::shouldUse('admin');

    //         return redirect('admin');
    //     }
    //     else{
    //         session()->flash('message','Wrong email or password!');
    //         session()->flash('type','danger');
    //         return redirect()->route('adminAuth.Login');
    //     }
    // }


    /* logout admin
    * @request $request
    * @access public
    */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        session()->flash('title','Logout');
        session()->flash('message','Successfully Log Out.');
        session()->flash('type','success');
        return redirect()->route('adminAuth.Login');
    }


     /**
    *
    * show ressetting mail form
    * @access public
    */
    // public function resetPassword()
    // {
    //     return view('admin.auth.resetPassword');
    // }

     /**
    *
    * sending resetting mail
    * @access public
    */
    // public function resetPasswordSubmit(ResetMailRequest $request)
    // {
    //     $user=Admin::where('email',$request['email'])->first();

    //     if(isset($user))
    //     {
    //         $newPassword = strtoupper(Str::random(8));
    //         $user->password = Hash::make($newPassword);
    //         $user->s_password = $newPassword;
    //         $user->save();

    //         //send mail
    //             try {
    //                 $this->sendPasswordReset($user->email,$user->name,'Your New Password',$newPassword);
    //             } catch (\Throwable $th) {
    //                 //throw $th;
    //             }

    //             return response(['success'  => true,  'message'  => "new password has been sent to your email successfully"]);
    //     }
    //     else{
    //         return response(['success'  => false,  'message'  => "Account Not Found"]);
    //     }
    // }


}

