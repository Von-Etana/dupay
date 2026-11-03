<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoginChallenge;
use App\Services\LoginChallengeService;
use App\Services\UserSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginChallengeController extends Controller
{
    public function verify(Request $request)
    {
        $data = $request->validate([
            'challenge_id' => 'required|uuid',
            'otp' => 'required|digits:6',
        ]);

        $challenge = LoginChallenge::with('user')->find($data['challenge_id']);
        if (! $challenge || ! $challenge->user) {
            return response(['success' => false, 'code' => 'otp_expired', 'message' => 'This login request has expired. Please sign in again.'], 422);
        }

        if (UserSecurityService::otpLocked($challenge->user)) {
            return response(['success' => false, 'code' => 'account_locked', 'message' => 'Your account is temporarily locked for 30 minutes after repeated incorrect codes.'], 423);
        }

        if ($challenge->consumed_at) {
            return response(['success' => false, 'code' => 'otp_used', 'message' => 'This code has already been used. Request a new code.'], 422);
        }

        if (now()->gte($challenge->expires_at)) {
            return response(['success' => false, 'code' => 'otp_expired', 'message' => 'This verification code has expired. Request a new code.'], 422);
        }

        if (! app(LoginChallengeService::class)->verify($challenge, $data['otp'])) {
            // A simultaneous successful verification may have consumed this code
            // while this request waited for the challenge row lock.
            $challenge->refresh();
            if ($challenge->consumed_at || now()->gte($challenge->expires_at)) {
                return response(['success' => false, 'code' => $challenge->consumed_at ? 'otp_used' : 'otp_expired', 'message' => 'This code is no longer valid. Request a new code.'], 422);
            }

            $locked = UserSecurityService::recordOtpFailure($challenge->user);
            return response([
                'success' => false,
                'code' => $locked ? 'otp_retry_limit' : 'otp_invalid',
                'message' => $locked
                    ? 'Six incorrect codes. Your account is locked for 30 minutes.'
                    : 'Incorrect verification code.',
            ], $locked ? 423 : 422);
        }

        UserSecurityService::resetOtpFailures($challenge->user);
        app(LoginChallengeService::class)->registerDevice($challenge->user, $challenge);
        $token = $challenge->user->createToken('authToken');
        UserSecurityService::recordEvent('otp_success', $challenge->user);

        return response(['success' => true, 'user' => $challenge->user, 'access_token' => $token->accessToken]);
    }

    public function resend(Request $request)
    {
        $data = $request->validate(['challenge_id' => 'required|uuid']);

        try {
            return DB::transaction(function () use ($data) {
                $old = LoginChallenge::with('user')->whereKey($data['challenge_id'])->lockForUpdate()->first();
                if (! $old || ! $old->user || $old->consumed_at) {
                    return response(['success' => false, 'code' => 'otp_expired', 'message' => 'This login request has expired. Sign in again to receive a new code.'], 422);
                }

                if (UserSecurityService::otpLocked($old->user)) {
                    return response(['success' => false, 'code' => 'account_locked', 'message' => 'Your account is temporarily locked for 30 minutes.'], 423);
                }

                if ($old->sent_at && now()->diffInSeconds($old->sent_at) < 60) {
                    return response(['success' => false, 'code' => 'resend_cooldown', 'message' => 'Please wait before requesting another code.'], 429);
                }

                if ((int) $old->send_count >= 6) {
                    return response(['success' => false, 'code' => 'otp_send_limit', 'message' => 'You have reached the limit of six login codes. Please sign in again later.'], 429);
                }

                $challenge = app(LoginChallengeService::class)->begin(
                    $old->user,
                    $old->device_id_hash,
                    true,
                    (int) $old->send_count + 1
                );

                return response([
                    'success' => true,
                    'code' => 'otp_required',
                    'challenge_id' => $challenge->id,
                    'send_count' => $challenge->send_count,
                    'remaining_sends' => max(0, 6 - (int) $challenge->send_count),
                    'message' => 'A new login verification code has been sent.',
                ]);
            });
        } catch (\RuntimeException $exception) {
            report($exception);
            return response(['success' => false, 'code' => 'otp_delivery_failed', 'message' => 'Unable to deliver the login code. Please try again.'], 503);
        }
    }

    public function send(Request $request)
    {
        return $this->resend($request);
    }
}
