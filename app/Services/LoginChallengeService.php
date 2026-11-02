<?php

namespace App\Services;

use App\Models\LoginChallenge;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginChallengeService
{
    public const TTL_MINUTES = 10;
    public const RESEND_SECONDS = 60;
    public const MAX_ATTEMPTS = 3;

    public function __construct(private UserSecurityService $security) {}

    public function begin(User $user, string $password, Request $request, string $purpose = 'api'): array
    {
        return DB::transaction(function () use ($user, $password, $request, $purpose) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked = $this->locked($user)) {
                return $locked;
            }
            if (!$this->security->verifyPassword($user, $password, $request)) {
                return $this->locked($user->fresh()) ?? $this->error('invalid_credentials', 'Invalid login details.', 401);
            }
            if ((int) $user->ban_status === 1) {
                return $this->error('account_blocked', 'Your account is blocked. Contact support.', 403);
            }
            // One live challenge per account; replacing a challenge never resets failures.
            LoginChallenge::where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now(), 'otp_hash' => null]);
            $id = Str::random(64);
            LoginChallenge::create([
                'id' => hash('sha256', $id), 'user_id' => $user->id, 'purpose' => $purpose,
                'device_hash' => $this->deviceHash($request),
                'password_fingerprint' => hash('sha256', $user->password),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
            return ['success' => false, 'code' => 'otp_required', 'challenge_id' => $id, 'expires_in' => self::TTL_MINUTES * 60, 'message' => 'Verify your email code to finish signing in.'];
        }, 3);
    }

    public function send(Request $request, string $purpose = 'api'): array
    {
        return $this->withChallenge($request, $purpose, function (User $user, LoginChallenge $challenge) use ($request) {
            $retry = $user->login_otp_sent_at ? max(0, now()->diffInSeconds($user->login_otp_sent_at->copy()->addSeconds(self::RESEND_SECONDS), false)) : 0;
            if ($retry > 0) {
                return $this->error('resend_cooldown', 'Please wait before requesting another code.', 429, ['retry_after' => $retry]);
            }
            $otp = (string) random_int(100000, 999999);
            $challenge->otp_hash = Hash::make($otp);
            $challenge->otp_expires_at = $challenge->expires_at;
            $challenge->sent_at = now();
            $user->login_otp_sent_at = now();
            // Serialize delivery with verification/resend. A failed delivery invalidates the code.
            try {
                Mail::raw("Your Dupay login code is {$otp}. It expires in 10 minutes or when this login challenge expires. Never share this code.", function ($mail) use ($user) {
                    $mail->to($user->email)->subject('Your Dupay login verification code');
                });
            } catch (\Throwable $exception) {
                $challenge->otp_hash = null;
                $challenge->save();
                $user->save();
                $this->security->recordEvent('otp_delivery_failed', $user, $request);
                Log::warning('Login OTP delivery failed', ['user_id' => $user->id, 'exception' => get_class($exception)]);
                return $this->error('email_delivery_failed', 'We could not send your code. Please try again in one minute.', 503, ['retry_after' => self::RESEND_SECONDS]);
            }
            $challenge->save();
            $user->save();
            $this->security->recordEvent('otp_sent', $user, $request);
            return ['success' => true, 'code' => 'otp_sent', 'retry_after' => self::RESEND_SECONDS, 'expires_in' => max(0, now()->diffInSeconds($challenge->expires_at, false)), 'message' => 'A verification code has been sent to your email.'];
        });
    }

    public function verify(Request $request, string $purpose = 'api'): array
    {
        return $this->withChallenge($request, $purpose, function (User $user, LoginChallenge $challenge) use ($request, $purpose) {
            if (!$challenge->otp_hash) {
                return $this->error('otp_not_sent', 'Request a code before verifying.', 422);
            }
            if (!$challenge->otp_expires_at || !$challenge->otp_expires_at->isFuture()) {
                return $this->error('otp_expired', 'This code has expired. Sign in again.', 410);
            }
            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                return $this->error('otp_retry_limit', 'Too many attempts. Sign in again to request a new challenge.', 429);
            }
            if (!Hash::check($request->input('otp'), $challenge->otp_hash)) {
                $challenge->attempts++;
                $user->otp_failed_attempts++;
                $this->security->recordEvent('otp_failure', $user, $request);
                $challenge->save();
                if ($user->otp_failed_attempts >= self::MAX_ATTEMPTS) {
                    $user->otp_locked_until = now()->addHours(6);
                    $user->save();
                    $this->security->revokeSessions($user, $request);
                    $this->security->recordEvent('otp_lock', $user, $request);
                    $this->security->recordEvent('account_lock', $user, $request);
                    $this->security->notifyCritical($user, 'otp_lock', $request);
                    return $this->locked($user) + ['attempts_remaining' => 0];
                }
                $user->save();
                return $this->error('invalid_otp', 'Incorrect verification code.', 422, ['attempts_remaining' => self::MAX_ATTEMPTS - $user->otp_failed_attempts]);
            }
            $challenge->consumed_at = now();
            $challenge->otp_hash = null;
            $challenge->save();
            $user->otp_failed_attempts = 0;
            $user->otp_locked_until = null;
            if ($request->filled('fcmToken')) {
                $user->fcmToken = $request->input('fcmToken');
            }
            $user->save();
            $device = TrustedDevice::where('user_id', $user->id)->where('device_hash', $challenge->device_hash)->first();
            $isNew = !$device || $device->revoked_at || !$device->expires_at->isFuture();
            TrustedDevice::updateOrCreate(['user_id' => $user->id, 'device_hash' => $challenge->device_hash], [
                'token_hash' => hash('sha256', Str::random(64)), 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2048),
                'last_used_at' => now(), 'expires_at' => now()->addDays(90), 'revoked_at' => null,
            ]);
            $this->security->recordEvent('otp_success', $user, $request);
            if ($isNew) {
                $this->security->recordEvent('new_device_login', $user, $request);
            }
            if ($purpose === 'web') {
                return ['success' => true, 'user_id' => $user->id, 'session_version' => $user->security_session_version];
            }
            // Token creation is inside the consumption transaction: rollback on issuer failure.
            $token = $user->createToken('authToken');
            $this->security->recordEvent('token_issued', $user, $request);
            return ['success' => true, 'code' => 'authenticated', 'access_token' => $token->accessToken, 'user' => $user->fresh('profile')];
        });
    }

    private function withChallenge(Request $request, string $purpose, callable $action): array
    {
        $id = hash('sha256', (string) $request->input('challenge_id'));
        $candidate = LoginChallenge::find($id);
        if (!$candidate) {
            return $this->error('challenge_expired', 'This login has expired. Sign in again.', 410);
        }
        return DB::transaction(function () use ($candidate, $id, $request, $purpose, $action) {
            // Always lock user first to avoid deadlocks with login/unlock/revocation.
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->firstOrFail();
            if ($locked = $this->locked($user)) {
                return $locked;
            }
            if ((int) $user->ban_status === 1) {
                return $this->error('account_blocked', 'Your account is blocked. Contact support.', 403);
            }
            if ($user->otp_locked_until) {
                $user->otp_locked_until = null;
                $user->otp_failed_attempts = 0;
                $user->save();
            }
            $challenge = LoginChallenge::whereKey($id)->lockForUpdate()->first();
            if (!$challenge || $challenge->consumed_at || !$challenge->expires_at->isFuture()
                || $challenge->purpose !== $purpose
                || !hash_equals($challenge->device_hash, $this->deviceHash($request))
                || !hash_equals($challenge->password_fingerprint, hash('sha256', $user->password))) {
                return $this->error('challenge_expired', 'This login has expired. Sign in again.', 410);
            }
            return $action($user, $challenge);
        });
    }

    public function locked(User $user): ?array
    {
        $until = collect([$user->password_locked_until, $user->otp_locked_until])->filter(fn ($date) => $date && $date->isFuture())->sort()->last();
        return $until ? $this->error('account_locked', 'Your account is temporarily locked. Try again after the lock expires or contact support.', 423, [
            'locked_until' => $until->toIso8601String(), 'retry_after' => max(1, now()->diffInSeconds($until, false)),
        ]) : null;
    }

    public function deviceHash(Request $request): string
    {
        return hash('sha256', (string) $request->input('device_id'));
    }

    private function error(string $code, string $message, int $status, array $extra = []): array
    {
        return ['success' => false, 'code' => $code, 'message' => $message, '_status' => $status] + $extra;
    }
}
