<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\SecurityAlertService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA challenge screen during login.
     */
    public function showChallenge(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return redirect()->route('adminAuth.Login');
        }

        // If admin has not enabled 2FA or is already verified, proceed
        if (! $admin->hasTwoFactorEnabled() || session('admin_2fa_verified')) {
            return redirect()->intended('/adminoffice');
        }

        // If preferred method is email and no code in cache, send one now
        if ($admin->two_factor_type === 'email' && ! Cache::has('admin_2fa_otp_' . $admin->id)) {
            TwoFactorAuthService::sendEmailOtp($admin);
        }

        $emailSent = Cache::has('admin_2fa_otp_' . $admin->id);
        $cooldownRemaining = (int) Cache::get('admin_2fa_resend_cd_' . $admin->id, 0) - time();
        $cooldown = max(0, $cooldownRemaining);

        return view('admin.auth.2fa_challenge', [
            'admin' => $admin,
            'emailSent' => $emailSent,
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * Process verification of the 2FA code during login.
     */
    public function verifyChallenge(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return redirect()->route('adminAuth.Login');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->code);
        $throttleKey = 'admin_2fa_attempts_' . $admin->id;
        $attempts = (int) Cache::get($throttleKey, 0);

        if ($attempts >= 6) {
            SecurityAlertService::record(
                'admin_2fa_locked',
                'critical',
                'Excessive 2FA verification attempts',
                "Admin @{$admin->username} exceeded maximum allowed 2FA attempts.",
                $admin->id,
                $request->ip(),
                ['username' => $admin->username, 'user_email' => $admin->email]
            );

            return back()->with('error', 'Too many failed verification attempts. Please wait 5 minutes before trying again.');
        }

        $verified = false;
        $methodUsed = 'totp';

        // 1. Try TOTP code if secret exists
        $secret = $admin->getDecryptedTwoFactorSecret();
        if ($secret && strlen(preg_replace('/\s+/', '', $code)) === 6) {
            if (TwoFactorAuthService::verifyCode($secret, $code)) {
                $verified = true;
                $methodUsed = 'authenticator_app';
            }
        }

        // 2. Try Email OTP if not already verified
        if (! $verified && strlen(preg_replace('/\s+/', '', $code)) === 6) {
            if (TwoFactorAuthService::verifyEmailOtp($admin, $code)) {
                $verified = true;
                $methodUsed = 'email_otp';
            }
        }

        // 3. Try Recovery Code if contains hyphen or is ~10-11 chars
        if (! $verified) {
            if (TwoFactorAuthService::verifyRecoveryCode($admin, $code)) {
                $verified = true;
                $methodUsed = 'recovery_code';
            }
        }

        if ($verified) {
            Cache::forget($throttleKey);
            Cache::forget('admin_2fa_otp_' . $admin->id);

            session([
                'admin_2fa_verified' => true,
                'admin_2fa_verified_at' => now(),
            ]);

            // Add activity log
            addSiteActitvity($admin, [
                'activity' => "Passed 2FA verification ({$methodUsed})",
                'actions' => '2fa_verify',
                'module' => 'Auth',
                'user_type' => '2',
                'extra' => json_encode(['method' => $methodUsed]),
            ]);

            // If a recovery code was used, warn the admin about remaining codes
            if ($methodUsed === 'recovery_code') {
                $remaining = TwoFactorAuthService::countRemainingRecoveryCodes($admin);
                session()->flash('warning', "You signed in using a backup recovery code. You have {$remaining} backup code(s) remaining.");
            }

            return redirect()->intended('/adminoffice');
        }

        // Increment failed attempts
        Cache::put($throttleKey, $attempts + 1, now()->addMinutes(5));

        if (($attempts + 1) >= 3) {
            SecurityAlertService::record(
                'admin_2fa_failed',
                'high',
                'Failed admin 2FA verification',
                "Multiple incorrect 2FA attempts for admin @{$admin->username}.",
                $admin->id,
                $request->ip(),
                ['attempts' => $attempts + 1, 'username' => $admin->username]
            );
        }

        return back()->with('error', 'Invalid verification code. Please check and try again.');
    }

    /**
     * Resend Email OTP code to the admin.
     */
    public function resendEmailOtp(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            return redirect()->route('adminAuth.Login');
        }

        $cdKey = 'admin_2fa_resend_cd_' . $admin->id;
        if (Cache::has($cdKey)) {
            $secondsLeft = max(1, Cache::get($cdKey) - time());
            return back()->with('error', "Please wait {$secondsLeft} second(s) before requesting another code.");
        }

        $sent = TwoFactorAuthService::sendEmailOtp($admin);
        if ($sent) {
            Cache::put($cdKey, time() + 60, 60);
            return back()->with('success', 'A fresh verification code has been sent to your email (' . $admin->email . ').');
        }

        return back()->with('error', 'Failed to send verification code. Please try again or use your Authenticator app.');
    }

    /**
     * Show 2FA management & configuration view in Admin Dashboard.
     */
    public function showSettings(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $appName = getSiteSettings('title') ?: 'Dupay';

        $tempSecret = session('admin_2fa_temp_secret');
        if (! $tempSecret && ! $admin->hasTwoFactorEnabled()) {
            $tempSecret = TwoFactorAuthService::generateSecret(16);
            session(['admin_2fa_temp_secret' => $tempSecret]);
        }

        $otpAuthUrl = $tempSecret
            ? TwoFactorAuthService::getOtpAuthUrl($appName, $admin->username, $tempSecret)
            : '';

        $qrCodeUrl = $otpAuthUrl
            ? TwoFactorAuthService::getQrCodeUrl($otpAuthUrl, 220)
            : '';

        $remainingRecoveryCodes = $admin->hasTwoFactorEnabled()
            ? TwoFactorAuthService::countRemainingRecoveryCodes($admin)
            : 0;

        return view('admin.security.2fa', [
            'admin' => $admin,
            'isEnabled' => $admin->hasTwoFactorEnabled(),
            'tempSecret' => $tempSecret,
            'qrCodeUrl' => $qrCodeUrl,
            'otpAuthUrl' => $otpAuthUrl,
            'remainingRecoveryCodes' => $remainingRecoveryCodes,
            'freshRecoveryCodes' => session('fresh_recovery_codes'),
        ]);
    }

    /**
     * Confirm TOTP setup with initial 6-digit code.
     */
    public function confirmTotp(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $request->validate([
            'code' => 'required|string',
        ]);

        $tempSecret = session('admin_2fa_temp_secret');
        if (! $tempSecret) {
            return back()->with('error', 'Setup session expired. Please refresh the page and try again.');
        }

        $code = trim($request->code);
        if (! TwoFactorAuthService::verifyCode($tempSecret, $code)) {
            return back()->with('error', 'Invalid verification code. Make sure the time on your authenticator device is accurate.');
        }

        // Generate 8 fresh recovery codes
        $recovery = TwoFactorAuthService::generateRecoveryCodes(8);

        $admin->two_factor_secret = Crypt::encryptString($tempSecret);
        $admin->two_factor_recovery_codes = $recovery['storage'];
        $admin->two_factor_enabled = true;
        $admin->two_factor_type = 'totp';
        $admin->two_factor_confirmed_at = now();
        $admin->save();

        session()->forget('admin_2fa_temp_secret');
        session(['admin_2fa_verified' => true]);

        SecurityAlertService::record(
            'admin_2fa_enabled',
            'medium',
            'Two-Factor Authentication Enabled',
            "Admin @{$admin->username} enabled Two-Factor Authentication (TOTP).",
            $admin->id,
            $request->ip(),
            ['username' => $admin->username, 'type' => 'totp']
        );

        return redirect()->route('admin.security.2fa')
            ->with('success', 'Two-Factor Authentication has been successfully enabled!')
            ->with('fresh_recovery_codes', $recovery['plain']);
    }

    /**
     * Enable Email-based 2FA.
     */
    public function enableEmail(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        // If not already enabled, generate recovery codes
        if (! $admin->hasTwoFactorEnabled()) {
            $recovery = TwoFactorAuthService::generateRecoveryCodes(8);
            $admin->two_factor_recovery_codes = $recovery['storage'];
            $admin->two_factor_enabled = true;
            $admin->two_factor_confirmed_at = now();
            session()->flash('fresh_recovery_codes', $recovery['plain']);
        }

        $admin->two_factor_type = 'email';
        $admin->save();

        session(['admin_2fa_verified' => true]);

        SecurityAlertService::record(
            'admin_2fa_updated',
            'medium',
            'Two-Factor Method Changed',
            "Admin @{$admin->username} set preferred 2FA method to Email OTP.",
            $admin->id,
            $request->ip(),
            ['username' => $admin->username, 'type' => 'email']
        );

        return back()->with('success', 'Preferred 2FA method updated to Email Verification.');
    }

    /**
     * Switch 2FA method back to Authenticator app.
     */
    public function switchMethod(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $request->validate([
            'method' => 'required|in:totp,email',
        ]);

        if ($request->method === 'totp' && empty($admin->two_factor_secret)) {
            return back()->with('error', 'Please configure an Authenticator app before switching to app-based 2FA.');
        }

        $admin->two_factor_type = $request->method;
        $admin->save();

        return back()->with('success', 'Preferred 2FA method updated successfully.');
    }

    /**
     * Regenerate backup recovery codes (requires current password).
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Incorrect password. Cannot regenerate recovery codes.');
        }

        $recovery = TwoFactorAuthService::generateRecoveryCodes(8);
        $admin->two_factor_recovery_codes = $recovery['storage'];
        $admin->save();

        SecurityAlertService::record(
            'admin_2fa_recovery_regenerated',
            'medium',
            '2FA Recovery Codes Regenerated',
            "Admin @{$admin->username} generated fresh 2FA backup recovery codes.",
            $admin->id,
            $request->ip(),
            ['username' => $admin->username]
        );

        return back()
            ->with('success', 'New backup recovery codes generated. Please store them securely!')
            ->with('fresh_recovery_codes', $recovery['plain']);
    }

    /**
     * Disable 2FA for the admin (requires current password).
     */
    public function disable(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Incorrect password. Two-factor authentication was not disabled.');
        }

        $admin->two_factor_enabled = false;
        $admin->two_factor_secret = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->save();

        session()->forget('admin_2fa_verified');

        SecurityAlertService::record(
            'admin_2fa_disabled',
            'high',
            'Two-Factor Authentication Disabled',
            "Admin @{$admin->username} disabled Two-Factor Authentication.",
            $admin->id,
            $request->ip(),
            ['username' => $admin->username, 'user_email' => $admin->email]
        );

        return back()->with('success', 'Two-Factor Authentication has been disabled.');
    }
}
