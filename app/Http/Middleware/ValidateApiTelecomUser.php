<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Illuminate\Support\Facades\Hash;

class ValidateApiTelecomUser
{

    public function handle($request, Closure $next)
    {
        if (!$request->bearerToken()) {
            return $next($request);
        }


        return $this->validateToken($request, $next);
    }

    private function validateToken($request, Closure $next)
    {
        try {
            Auth::shouldUse('api');
            $user = Auth::user();

            if(!isset($_SERVER['HTTP_ISMART'])  || ($_SERVER['HTTP_ISMART'] != 'app' && $_SERVER['HTTP_ISMART'] != "api")){
                return response(['success' => false, 'message' => "ismart header field is required."]);
            }

            if(!isset($_SERVER['HTTP_ISMART'])  || ($_SERVER['HTTP_ISMART'] != 'app')){
            if($user->level->enable_api == "0"){
                return response(['success' => false, 'message' => "Use of api not allow"]);
            }
            }

        } catch (\Throwable $th) {
            return response(['success' => false, 'message' => "Login Expire Kindly Relogin"]);
        }

        return $next($request);
    }
}
