<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\LoginChallenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class UserSecurityService
{
    public const MAX_PASSWORD_ATTEMPTS = 5;
    public const PASSWORD_LOCKOUT_MINUTES = 15;

    /** Verify and update under a row lock so web/API attempts share one counter. */
    public function verifyPassword(User $user, string $password, Request $request): bool
    {
        return DB::transaction(function () use ($user, $password, $request) {
            $account = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if (($account->password_locked_until && $account->password_locked_until->isFuture())
                || ($account->otp_locked_until && $account->otp_locked_until->isFuture())) {
                $this->recordEvent('password_login_blocked', $account, $request);
                return false;
            }

            if ($account->password_locked_until) {
                $account->password_failed_attempts = 0;
                $account->password_locked_until = null;
            }

            if (!Hash::check($password, $account->password)) {
                $account->password_failed_attempts++;
                $this->recordEvent('password_failed', $account, $request);
                if ($account->password_failed_attempts >= self::MAX_PASSWORD_ATTEMPTS) {
                    $account->password_locked_until = now()->addMinutes(self::PASSWORD_LOCKOUT_MINUTES);
                    $this->recordEvent('password_locked', $account, $request);
                    $this->recordEvent('account_lock', $account, $request);
                    $this->revokeSessions($account, $request);
                    $this->notifyCritical($account, 'password_locked', $request);
                }
                $account->save();
                return false;
            }

            $account->password_failed_attempts = 0;
            $account->password_locked_until = null;
            $account->save();
            $this->recordEvent('password_verified', $account, $request);
            return true;
        }, 3);
    }

    /** Only explicit context is stored; passwords, OTPs and request bodies are excluded. */
    public function recordEvent(string $event, ?User $user, Request $request, ?int $adminId = null, ?string $reason = null): ?SecurityEvent
    {
        try {
            if (!Schema::hasTable('security_events')) {
                return null;
            }

            $payload = [
                'user_id' => $user ? $user->getKey() : null,
                'event' => $event,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2048),
            ];

            if (Schema::hasColumn('security_events', 'device_hash')) {
                $payload['device_hash'] = $request->filled('device_id') ? hash('sha256', (string) $request->input('device_id')) : null;
            }
            if (Schema::hasColumn('security_events', 'admin_id')) {
                $payload['admin_id'] = $adminId;
            }
            if (Schema::hasColumn('security_events', 'reason')) {
                $payload['reason'] = $reason;
            }
            $requestId = $request->attributes->get('request_id') ?? $request->header('X-Request-ID');
            if ($requestId && Schema::hasColumn('security_events', 'request_id')) {
                $payload['request_id'] = $requestId;
            }

            try {
                Log::info("Security event: {$event}", [
                    'event' => $event,
                    'user_id' => $payload['user_id'],
                    'admin_id' => $adminId,
                    'request_id' => $requestId,
                    'ip_address' => $payload['ip_address'],
                ]);
            } catch (\Throwable $e) {}

            return SecurityEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning("Failed to record security event: {$event} - " . $e->getMessage());
            return null;
        }
    }

    /** Caller must hold the user row lock. Includes refresh tokens and browser sessions. */
    public function revokeSessions(User $user, Request $request, ?int $adminId = null, ?string $reason = null): void
    {
        if (Schema::hasTable('oauth_access_tokens')) {
            $ids = DB::table('oauth_access_tokens')->where('user_id', $user->id)->pluck('id');
            if (Schema::hasTable('oauth_refresh_tokens')) {
                DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $ids)->update(['revoked' => true]);
            }
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->update(['revoked' => true]);
        }
        if (Schema::hasTable('login_challenges')) {
            LoginChallenge::where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now(), 'otp_hash' => null]);
        }
        if (Schema::hasColumn('users', 'security_session_version')) {
            $user->security_session_version++;
        }
        $user->save();
        $this->recordEvent('session_revocation', $user, $request, $adminId, $reason);
    }

    public function unlock(User $user, Request $request, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($user, $request, $adminId, $reason) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->password_failed_attempts = 0;
            $user->password_locked_until = null;
            $user->otp_failed_attempts = 0;
            $user->otp_locked_until = null;
            $this->revokeSessions($user, $request, $adminId, $reason);
            $this->recordEvent('admin_unlock', $user, $request, $adminId, $reason);
        });
    }

    public function notifyCritical(User $user, string $event, Request $request): void
    {
        if (!app()->bound('cache') || !app()->bound('log')) {
            return;
        }
        try {
            SecurityAlertService::record('user_'.$event, 'critical', 'User account locked',
                "User {$user->id} was locked after repeated authentication failures.", null, $request->ip(), ['user_id' => $user->id]);
        } catch (\Throwable $exception) {
            error_log('Security notification failed for user '.$user->id.' ('.$event.'): '.get_class($exception));
        }
    }

    public function notifyOtpLock(User $user, Request $request, int $lockMinutes): void
    {
        $lockedUntil = $user->otp_locked_until ?: now()->addMinutes($lockMinutes);

        if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw(
                    "Your Dupay account has been temporarily locked after six incorrect login verification codes. " .
                    "The lock will end at {$lockedUntil->toDateTimeString()} server time. Contact support if you need help before then.",
                    function ($mail) use ($user) {
                        $mail->to($user->email)->subject('Dupay account temporarily locked');
                    }
                );
            } catch (\Throwable $exception) {
                Log::warning('OTP lock notification to user failed', [
                    'user_id' => $user->id,
                    'exception' => get_class($exception),
                ]);
            }
        }

        try {
            SecurityAlertService::record(
                'otp_account_locked',
                'critical',
                'User account locked after OTP failures',
                "User {$user->id} was locked for {$lockMinutes} minutes after six incorrect login verification codes.",
                null,
                $request->ip(),
                ['user_id' => $user->id, 'attempts' => 6, 'lock_minutes' => $lockMinutes, 'locked_until' => $lockedUntil->toIso8601String()]
            );
        } catch (\Throwable $exception) {
            Log::warning('OTP lock notification to administrators failed', [
                'user_id' => $user->id,
                'exception' => get_class($exception),
            ]);
        }
    }
}
