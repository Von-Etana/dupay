<?php

namespace App\Http\Middleware;

use App\Models\Admin as ModelsAdmin;
use App\Models\UserBranch;
use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
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
        if(\Schema::hasTable('migrations'))
        {
            if(!Auth::guard('admin')->check())
            {
                return redirect()->route('adminAuth.Login');
            }

            //add online
            $user= ModelsAdmin::where('id',auth()->guard('admin')->user()->id)
                        ->update([
                            'last_activity'=>now()
                        ]);

            // //share settings
            // $info=setting('info');
            // $whatsapp=setting('whatsapp');
            // $email=setting('emails');
            // $api_keys=setting('api_keys');

            // view()->share([
            //     'info'=>$info,
            //     'whatsapp'=>$whatsapp,
            //     'api_keys'=>$api_keys,
            //     'email'=>$email,
            // ]);
        }

        return $next($request);
    }
}
