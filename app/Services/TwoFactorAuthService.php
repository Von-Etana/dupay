<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new cryptographically secure Base32 TOTP secret.
     */
    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        $charCount = strlen(self::BASE32_CHARS);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $charCount - 1)];
        }
        return $secret;
    }

    /**
     * Decode a Base32 string to raw binary.
     */
    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(str_replace('=', '', $b32));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($b32); $i++) {
            $val = strpos(self::BASE32_CHARS, $b32[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Calculate 6-digit TOTP code for a given timestamp (RFC 6238).
     */
    public static function getCode(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string
    {
        $timestamp = $timestamp ?? time();
        $counter = pack('N*', 0, (int) floor($timestamp / $period));
        $hash = hash_hmac('sha1', $counter, self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24) |
                  ((ord($hash[$offset + 1]) & 0xFF) << 16) |
                  ((ord($hash[$offset + 2]) & 0xFF) << 8) |
                  (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** $digits);
        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit TOTP code with time drift window.
     */
    public static function verifyCode(string $secret, string $code, ?int $timestamp = null, int $window = 1, int $period = 30): bool
    {
        $cleanCode = preg_replace('/\s+/', '', $code);
        if (strlen($cleanCode) !== 6 || ! ctype_digit($cleanCode)) {
            return false;
        }

        $timestamp = $timestamp ?? time();

        for ($drift = -$window; $drift <= $window; $drift++) {
            $checkTime = $timestamp + ($drift * $period);
            if (hash_equals(self::getCode($secret, $checkTime, $period, 6), $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate standard otpauth URI for QR codes.
     */
    public static function getOtpAuthUrl(string $company, string $accountName, string $secret): string
    {
        $encodedCompany = rawurlencode($company);
        $encodedAccount = rawurlencode($accountName);
        return "otpauth://totp/{$encodedCompany}:{$encodedAccount}?secret={$secret}&issuer={$encodedCompany}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Get image URL to render QR code for scanning.
     */
    public static function getQrCodeUrl(string $otpAuthUrl, int $size = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&margin=10&data=' . rawurlencode($otpAuthUrl);
    }

    /**
     * Generate a set of 8 single-use recovery codes.
     * Returns an array of plain text codes for display, and structured array with hashes for storage.
     *
     * @return array{plain: array<string>, storage: array<array{code_hash: string, used_at: ?string}>}
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        $storage = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5))); // 10 chars
            $code = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
            $plain[] = $code;
            $storage[] = [
                'code_hash' => Hash::make($code),
                'used_at' => null,
            ];
        }

        return [
            'plain' => $plain,
            'storage' => $storage,
        ];
    }

    /**
     * Verify and consume a recovery code for the admin.
     */
    public static function verifyRecoveryCode(Admin $admin, string $code): bool
    {
        $cleanCode = strtoupper(trim($code));
        $recoveryCodes = is_array($admin->two_factor_recovery_codes)
            ? $admin->two_factor_recovery_codes
            : json_decode((string) $admin->two_factor_recovery_codes, true);

        if (empty($recoveryCodes) || ! is_array($recoveryCodes)) {
            return false;
        }

        foreach ($recoveryCodes as $index => $item) {
            if (empty($item['used_at']) && ! empty($item['code_hash'])) {
                if (Hash::check($cleanCode, $item['code_hash'])) {
                    $recoveryCodes[$index]['used_at'] = now()->toDateTimeString();
                    $admin->two_factor_recovery_codes = $recoveryCodes;
                    $admin->save();

                    Log::info('Admin used 2FA recovery code', [
                        'admin_id' => $admin->id,
                        'remaining' => count(array_filter($recoveryCodes, fn ($c) => empty($c['used_at']))),
                    ]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Count remaining unused recovery codes.
     */
    public static function countRemainingRecoveryCodes(Admin $admin): int
    {
        $codes = is_array($admin->two_factor_recovery_codes)
            ? $admin->two_factor_recovery_codes
            : json_decode((string) $admin->two_factor_recovery_codes, true);

        if (empty($codes) || ! is_array($codes)) {
            return 0;
        }

        return count(array_filter($codes, fn ($c) => empty($c['used_at'])));
    }

    /**
     * Send an Email OTP to the admin for 2FA.
     */
    public static function sendEmailOtp(Admin $admin): bool
    {
        $recipient = $admin->email;
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Cannot send 2FA email OTP: invalid admin email', ['admin_id' => $admin->id]);
            return false;
        }

        // Generate 6-digit numeric OTP
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        Cache::put('admin_2fa_otp_' . $admin->id, [
            'hash' => Hash::make($otp),
            'expires_at' => $expiresAt->timestamp,
        ], 600);

        try {
            Mail::send('emails.admin_2fa_otp', [
                'admin' => $admin,
                'otp' => $otp,
                'expires_in' => '10 minutes',
            ], function ($message) use ($recipient) {
                $message->to($recipient)->subject('[Dupay Admin] Your 2FA Verification Code: ' . substr($recipient, 0, 3) . '***');
            });

            Log::info('Admin 2FA Email OTP sent', ['admin_id' => $admin->id]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed sending admin 2FA email OTP, trying raw fallback', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            try {
                Mail::raw(
                    "Your Dupay Admin Two-Factor Authentication code is: {$otp}\n\n" .
                    "This code will expire in 10 minutes.\n" .
                    "If you did not attempt to log in to the admin panel, please secure your account immediately.",
                    function ($message) use ($recipient) {
                        $message->to($recipient)->subject('[Dupay Admin] Your 2FA Verification Code');
                    }
                );
                return true;
            } catch (\Throwable $rawException) {
                Log::error('All attempts to send admin 2FA email failed', ['error' => $rawException->getMessage()]);
                return false;
            }
        }
    }

    /**
     * Verify an Email OTP.
     */
    public static function verifyEmailOtp(Admin $admin, string $code): bool
    {
        $cleanCode = preg_replace('/\s+/', '', $code);
        $cacheData = Cache::get('admin_2fa_otp_' . $admin->id);

        if (! $cacheData || empty($cacheData['hash'])) {
            return false;
        }

        if (time() > ($cacheData['expires_at'] ?? 0)) {
            Cache::forget('admin_2fa_otp_' . $admin->id);
            return false;
        }

        if (Hash::check($cleanCode, $cacheData['hash'])) {
            Cache::forget('admin_2fa_otp_' . $admin->id);
            return true;
        }

        return false;
    }
}
