<?php

namespace App\Http\Middleware;

use App\Services\TransactionLimitService;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Early, friendly rejection when a request would exceed the user's daily limit.
 *
 * The previous version had two holes:
 *
 *   1. It compared today's total to the limit WITHOUT adding the amount being
 *      spent — `if ($totalTransaction >= $limit)`. A user who had spent nothing
 *      passed the check and could then push through a single transaction of any
 *      size. That is the bypass this rewrite closes.
 *   2. It only enforced the limit for users whose wallet KYC was unapproved, so
 *      a verified account had no ceiling at all. Verified users now get their
 *      own higher limit instead of an exemption (see TransactionLimitService).
 *
 * This middleware is a convenience, not the guarantee: it can only see amounts
 * the client sends, and it runs outside the debit transaction. The authoritative
 * check is in userBalanceAction(), under the user row lock.
 */
class CheckUserTransactionLimit
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user() ?: Auth::user();

        if (!$user) {
            return response(['success' => false, 'message' => 'Login Expire Kindly Relogin']);
        }

        $amount = $this->requestedAmount($request);

        // Routes that price server-side (a data or cable plan id rather than an
        // amount) report 0 here. Passing 0 still catches the "already at or over
        // the limit" case; the exact amount is checked at the debit.
        $check = TransactionLimitService::check($user, $amount, 'NGN');

        if (empty($check['success'])) {
            return response([
                'success'         => false,
                'message'         => $check['message'],
                'daily_limit'     => $check['limit'],
                'spent_today'     => $check['spent'],
                'remaining_limit' => $check['remaining'],
            ]);
        }

        return $next($request);
    }

    /**
     * Best-effort read of what the user is about to spend. Amounts arrive as
     * formatted strings ("10,000") on several endpoints.
     */
    protected function requestedAmount($request): float
    {
        foreach (['amount', 'total', 'value'] as $field) {
            $raw = $request->input($field);
            if ($raw !== null && $raw !== '') {
                return (float) str_replace(',', '', (string) $raw);
            }
        }

        return 0.0;
    }
}
