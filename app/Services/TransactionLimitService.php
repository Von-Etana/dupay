<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for the daily transaction limit.
 *
 * Before this class there were three separate implementations, each wrong in a
 * different way:
 *
 *   1. CheckUserTransactionLimit middleware compared today's total to the limit
 *      WITHOUT adding the amount being spent, so a user at zero spend could push
 *      any single amount through, however large.
 *   2. checkDailyTransactionLimit() in GeneralHelper only summed two service
 *      types ("Fund Withdrawal", "Airtime Topup"), so data, cable, bills, P2P,
 *      epins, exams, card funding and conversions never counted.
 *   3. The inline check in TransferController::transferFund had no date filter
 *      at all — it summed transfers for all time, permanently locking users out.
 *
 * Everything now goes through check(). The authoritative call site is
 * userBalanceAction() in GeneralHelper, which runs inside a DB transaction with
 * the user row locked — that is what makes the limit unbypassable, both by
 * routes that forget the middleware and by two requests racing each other.
 */
class TransactionLimitService
{
    /**
     * Transaction statuses that do NOT count toward the daily spend.
     * A purchase that failed or was refunded must not eat the user's limit.
     */
    public const UNCOUNTED_STATUSES = ['cancel', 'refund', 'unsuccessful', 'failed', 'reversed'];

    /** Value that means "no limit" in the admin-editable limit columns. */
    public const UNLIMITED = '-';

    /**
     * Master switch. When the site setting is off, no limit is enforced —
     * matches the behaviour the middleware already had.
     */
    public static function isEnabled(): bool
    {
        return getSiteSettings('limit_daily_transaction') === 'Yes';
    }

    /**
     * A user is "verified" once their wallet KYC has been approved. Verified
     * users get their own, higher limit.
     */
    public static function isVerified($user): bool
    {
        return $user
            && $user->walletVerification
            && (string) $user->walletVerification->status === '1';
    }

    /**
     * The limit that applies to this user, in naira.
     *
     * Resolution order — per-user value first so the admin "adjust limit"
     * screen keeps working, then the account level as the default:
     *
     *   verified   -> users.verified_daily_transaction_limit
     *              -> user_levels.verified_daily_transaction_limit
     *              -> (falls through to the unverified limit if neither is set)
     *   unverified -> users.daily_transaction_limit
     *              -> user_levels.daily_transaction_limit
     *
     * Returns null when the user has no limit at all.
     */
    public static function limitFor($user): ?float
    {
        if (!$user) {
            return null;
        }

        $candidates = [];

        if (self::isVerified($user)) {
            $candidates[] = $user->verified_daily_transaction_limit ?? null;
            $candidates[] = optional($user->level)->verified_daily_transaction_limit;
        }

        $candidates[] = $user->daily_transaction_limit ?? null;
        $candidates[] = optional($user->level)->daily_transaction_limit;

        foreach ($candidates as $candidate) {
            $resolved = self::normalizeLimit($candidate);
            if ($resolved === 'unlimited') {
                return null;
            }
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Turns a raw column value into a usable limit.
     *
     * Returns a float for a real limit, the string 'unlimited' when the column
     * explicitly says "no limit" ("-"), or null when the column is simply unset
     * so the caller should fall through to the next candidate.
     *
     * Note 0 counts as "unset", not "block everything" — the per-user column
     * defaults to 0 for most accounts and treating that as a zero limit would
     * stop every transaction on the platform.
     *
     * @return float|string|null
     */
    protected static function normalizeLimit($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (trim((string) $value) === self::UNLIMITED) {
            return 'unlimited';
        }

        $numeric = (float) str_replace(',', '', (string) $value);

        return $numeric > 0 ? $numeric : null;
    }

    /**
     * Total already spent today, across EVERY channel.
     *
     * Counts every debit row rather than an allow-list of service types, so a
     * service added later is covered automatically instead of silently escaping
     * the limit. Scoped to one currency: without this a $100 USD card debit
     * would burn ₦100 of a naira limit.
     */
    public static function spentToday($user, string $currency = 'NGN'): float
    {
        if (!$user) {
            return 0.0;
        }

        return (float) $user->transaction()
            ->where('type', '0')                       // 0 = debit, 1 = credit
            ->where('currency', $currency)
            ->whereNotIn('status', self::UNCOUNTED_STATUSES)
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
    }

    /**
     * Would spending [$amount] now push the user past their daily limit?
     *
     * Returns:
     *   ['success' => true,  'limit' => ?float, 'spent' => float, 'remaining' => ?float]
     *   ['success' => false, 'message' => string, ...same keys]
     *
     * Fails OPEN on an unexpected error (logged, transaction allowed). A bug in
     * limit resolution must not take the whole platform's payments down; an
     * actually-exceeded limit still fails closed.
     */
    public static function check($user, $amount, string $currency = 'NGN'): array
    {
        try {
            if (!self::isEnabled()) {
                return ['success' => true, 'limit' => null, 'spent' => 0.0, 'remaining' => null];
            }

            $limit = self::limitFor($user);
            if ($limit === null) {
                return ['success' => true, 'limit' => null, 'spent' => 0.0, 'remaining' => null];
            }

            $amount = (float) str_replace(',', '', (string) $amount);
            $spent  = self::spentToday($user, $currency);
            $after  = $spent + $amount;

            if ($after > $limit) {
                $remaining = max($limit - $spent, 0);

                return [
                    'success'   => false,
                    'limit'     => $limit,
                    'spent'     => $spent,
                    'remaining' => $remaining,
                    'message'   => self::message($limit, $spent, $remaining, $user),
                ];
            }

            return [
                'success'   => true,
                'limit'     => $limit,
                'spent'     => $spent,
                'remaining' => max($limit - $after, 0),
            ];
        } catch (\Throwable $e) {
            Log::error('TransactionLimitService::check failed, allowing transaction: ' . $e->getMessage());

            return ['success' => true, 'limit' => null, 'spent' => 0.0, 'remaining' => null];
        }
    }

    /**
     * The message the user sees. Says what the limit is, what's left, and — for
     * unverified users — how to raise it, since that is the actionable fix.
     */
    protected static function message(float $limit, float $spent, float $remaining, $user): string
    {
        $message = 'Daily transaction limit exceeded. Your limit is ₦'
            . number_format($limit, 2) . ' per day and you have already spent ₦'
            . number_format($spent, 2) . ' today.';

        $message .= $remaining > 0
            ? ' You can still spend up to ₦' . number_format($remaining, 2) . ' today.'
            : ' You have no remaining limit today.';

        if (!self::isVerified($user)) {
            $message .= ' Verify your account to increase your daily limit.';
        }

        return $message;
    }
}
