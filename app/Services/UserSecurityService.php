<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserSecurityService
{
    public static function deviceHash(?string $id): ?string
    {
        return $id ? hash('sha256', trim($id)) : null;
    }

    public static function passwordLocked(User $user): bool
    {
        return $user->login_locked_until && now()->lt($user->login_locked_until);
    }

    public static function otpLocked(User $user): bool
    {
        return $user->otp_locked_until && now()->lt($user->otp_locked_until);
    }

    public static function recordEvent(string $type, ?User $user = null, array $meta = [], string $severity = 'medium'): void
    {
        if (DB::getSchemaBuilder()->hasTable('security_events')) {
            DB::table('security_events')->insert([
                'user_id' => $user?->id,
                'event_type' => $type,
                'severity' => $severity,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 512, ''),
                'metadata' => json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function recordPasswordFailure(User $user): bool
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $locked = $attempts >= 6;
        $user->failed_login_attempts = $locked ? 0 : $attempts;
        $user->login_locked_until = $locked ? now()->addHours(2) : null;
        $user->save();
        self::recordEvent($locked ? 'account_locked' : 'login_failed', $user, [
            'attempts' => $attempts,
            'lock_hours' => $locked ? 2 : 0,
        ], $locked ? 'critical' : 'high');

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
        $locked = DB::transaction(function () use ($user) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempts = (int) $lockedUser->otp_failed_attempts + 1;
            $shouldLock = $attempts >= 6;
            $lockedUser->otp_failed_attempts = $shouldLock ? 0 : $attempts;
            $lockedUser->otp_locked_until = $shouldLock ? now()->addMinutes(30) : null;
            $lockedUser->save();

            self::recordEvent($shouldLock ? 'otp_locked' : 'otp_failed', $lockedUser, [
                'attempts' => $attempts,
                'lock_minutes' => $shouldLock ? 30 : 0,
            ], $shouldLock ? 'critical' : 'high');

            if ($shouldLock) {
                DB::table('oauth_access_tokens')
                    ->where('user_id', $lockedUser->id)
                    ->where('revoked', false)
                    ->update(['revoked' => true]);
                self::recordEvent('session_revocation', $lockedUser, ['reason' => 'otp_lockout'], 'critical');
            }

            return $shouldLock;
        });

        if ($locked) {
            $freshUser = $user->fresh();
            if ($freshUser) {
                self::notifyOtpLock($freshUser);
            }
        }

        return $locked;
    }

    private static function notifyOtpLock(User $user): void
    {
        $title = 'Dupay account temporarily locked';
        $message = 'Your account is locked for 30 minutes after six incorrect login verification codes. An administrator may unlock it sooner. If this was not you, contact support.';

        try {
            if ($user->email) {
                Mail::raw($message, function ($mail) use ($user, $title) {
                    $mail->to($user->email)->subject($title);
                });
            }
        } catch (\Throwable $exception) {
            Log::warning('User OTP lock email failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            if (! empty($user->fcmToken)) {
                $result = sendFcmNotification($user->fcmToken, $title, $message, [
                    'type' => 'account_locked',
                    'code' => 'account_locked',
                    'lock_minutes' => '30',
                    'user_id' => (string) $user->id,
                ]);
                if (is_array($result) && ($result['status'] ?? true) === false) {
                    Log::warning('User OTP lock push notification failed', [
                        'user_id' => $user->id,
                        'message' => $result['message'] ?? 'Unknown FCM failure',
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('User OTP lock push notification failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            SecurityAlertService::record(
                'otp_account_locked',
                'critical',
                'User account locked after OTP failures',
                'Account was locked for 30 minutes after six incorrect login verification codes.',
                null,
                request()->ip(),
                ['user_id' => $user->id, 'attempts' => 6, 'lock_minutes' => 30]
            );
        } catch (\Throwable $exception) {
            Log::error('Admin OTP lock alert failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function resetOtpFailures(User $user): void
    {
        $user->otp_failed_attempts = 0;
        $user->otp_locked_until = null;
        $user->save();
    }
}
