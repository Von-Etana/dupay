<?php

namespace App\Http\Middleware;

use App\Services\UserBlockService;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Refuses every money-movement request from a blocked or banned account.
 *
 * Applied to all user-initiated debit routes in routes/api/v1/user.php. This is
 * the friendly, early rejection — the authoritative one lives in
 * userBalanceAction(), so a route that is added later without this middleware
 * still cannot move a blocked user's money.
 */
class EnsureUserNotBlocked
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user() ?: Auth::user();

        if (UserBlockService::isBlocked($user)) {
            return response([
                'success' => false,
                'message' => UserBlockService::message(),
            ]);
        }

        return $next($request);
    }
}
