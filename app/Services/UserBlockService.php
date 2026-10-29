<?php

namespace App\Services;

/**
 * Single source of truth for "is this account allowed to move money?".
 *
 * The blocked check used to be copy-pasted inline in seven controllers, which
 * is exactly how it came to be missing from TransferController::transferFund —
 * the Dupay-to-Dupay P2P transfer — as well as pay-bill, virtual-card funding
 * and both convert-currency routes. A blocked user could still send money to
 * another Dupay user.
 *
 * Both flags are honoured:
 *   blocked_status — set by the admin "Block user" toggle
 *   ban_status     — the older ban flag, already enforced at login
 */
class UserBlockService
{
    public static function isBlocked($user): bool
    {
        if (!$user) {
            return false;
        }

        return self::flagIsSet($user->blocked_status ?? null)
            || self::flagIsSet($user->ban_status ?? null);
    }

    /**
     * These columns are strings/enums ('0' / '1'), so a loose == comparison is
     * unsafe: '0' == true is false in PHP, but so is any non-'1' value being
     * treated as truthy elsewhere. Compare explicitly.
     */
    protected static function flagIsSet($value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array((string) $value, ['1', 'true', 'yes'], true);
    }

    public static function message(): string
    {
        return 'Your account has been restricted and cannot carry out transactions. '
            . 'Please contact support.';
    }
}
