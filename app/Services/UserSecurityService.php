<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSecurityService
{
    public static function deviceHash(?string $deviceId): ?string
    {
        return $deviceId ? hash('sha256', trim($deviceId)) : null;
    }

    public static function passwordLocked(User $user): bool
    {
        return $user->login_locked_until && now()->lt($user->login_locked_until);
    }

    public static function otpLocked(User $user): bool
    {
        return $user->otp_locked_until && now()->lt($user->otp_locked_until);
    }

    public static function recordEvent(string $type, ?User $user = null, array $metadata = [], string $severity = 'medium'): void
    {
        if (DB::getSchemaBuilder()->hasTable('security_events')) {
            DB::table('security_events')->insert([
                'user_id' => $user?->id,
                'event_type' => $type,
                'severity' => $severity,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 512, ''),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function recordPasswordFailure(User $user): bool
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $locked = $attempts >= 5;
        $user->failed_login_attempts = $locked ? 0 : $attempts;
        $user->login_locked_until = $locked ? now()->addHours(6) : null;
        $user->save();
        self::recordEvent($locked ? 'account_locked' : 'login_failed', $user, ['attempts' => $attempts], $locked ? 'critical' : 'high');
        return $locked;
    }

    public static function resetPasswordFailures(User $user): void
    {
        $user->failed_login_attempts = 0;
        $user->login_locked_until = null;
        $user->save();
    }

    public static function recordOtpFailure(User $user): bool
    {
        $attempts = (int) $user->otp_failed_attempts + 1;
        $locked = $attempts >= 3;
        $user->otp_failed_attempts = $locked ? 0 : $attempts;
        $user->otp_locked_until = $locked ? now()->addHours(6) : null;
        $user->save();
        self::recordEvent($locked ? 'otp_locked' : 'otp_failed', $user, ['attempts' => $attempts], $locked ? 'critical' : 'high');
        return $locked;
    }

    public static function resetOtpFailures(User $user): void
    {
        $user->otp_failed_attempts = 0;
        $user->otp_locked_until = null;
        $user->save();
    }
}
