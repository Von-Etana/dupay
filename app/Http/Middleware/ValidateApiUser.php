<?php

namespace App\Http\Middleware;

use Closure;
use Auth;


class ValidateApiUser
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
        } catch (\Throwable $th) {
            return response(['success' => false, 'message' => "Login Expire Kindly Relogin"]);
        }

        return $next($request);
    }
}
