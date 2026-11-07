<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\SecurityAlertService;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/adminoffice/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }
    protected function guard()
    {

        return Auth::guard('admin');
    }
    /*public function email()
    {
        return 'email';
    }*/
    public function logout()
    {
        $ac['activity'] = "Logout from admin panel";
        $ac['actions'] = "logout";
        $ac['user_type'] = "2";
        addSiteActitvity($this->guard('admin')->user(), $ac);
        $this->guard('admin')->logout();
        session()->flash('title','Logout');
        session()->flash('message','Successfully Log Out.');
        session()->flash('type','success');

        return redirect()->route("adminAuth.Login");
    }

    public function authenticated(Request $request, $user)
    {

        $ac['activity'] = "Login to admin panel";
        $ac['actions'] = "login";
        $ac['user_type'] = "2";
        $ac['module'] = "Login";
        addSiteActitvity($user, $ac);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        SecurityAlertService::record(
            'admin_login_failed',
            'high',
            'Failed admin login',
            'An admin login attempt failed.',
            null,
            $request->ip(),
            ['login' => substr((string) $request->input($this->username()), 0, 120)]
        );

        return parent::sendFailedLoginResponse($request);
    }
}
