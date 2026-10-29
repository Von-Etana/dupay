<?php

namespace App\Http\Middleware;

use App\Models\User as ModelsUser;
use Closure;
use Auth;
use Cache;
class User
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
            if(!Auth::guard('user')->check())
            {
                return redirect()->route('userLogin');
            }

            //add online
            ModelsUser::where('id',auth()->guard('user')->user()->id)
                    ->update([
                        'last_activity'=>now()
                    ]);

            // //share settings
            // $info=setting('info');
            // $api_keys=setting('api_keys');


            // view()->share([
            //     'info'=>$info,
            //     'api_keys'=>$api_keys
            // ]);
        }

        return $next($request);
    }
}
