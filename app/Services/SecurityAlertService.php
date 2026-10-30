<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityAlertService
{
    public static function record(
        string $type,
        string $severity,
        string $title,
        string $message,
        ?int $adminId = null,
        ?string $ipAddress = null,
        array $metadata = []
    ): ?SecurityAlert {
        $throttleKey = 'security-alert:' . sha1($type . '|' . ($ipAddress ?? 'unknown') . '|' . ($adminId ?? 'guest'));
        if (! Cache::add($throttleKey, true, now()->addMinutes(5))) {
            return null;
        }

        // If metadata has user_id but not username or user_name, auto-resolve
        if (! empty($metadata['user_id']) && (empty($metadata['username']) || empty($metadata['user_name']))) {
            try {
                $user = User::with('profile')->find($metadata['user_id']);
                if ($user) {
                    $metadata['username'] = $metadata['username'] ?? ($user->profile?->username ?? $user->username ?? ('id:' . $user->id));
                    $metadata['user_name'] = $metadata['user_name'] ?? (trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: $user->name);
                    $metadata['user_email'] = $metadata['user_email'] ?? $user->email;
                }
            } catch (\Throwable $e) {
                // Best effort metadata enhancement
            }
        }

        $alert = SecurityAlert::create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'admin_id' => $adminId,
            'ip_address' => $ipAddress,
            'metadata' => $metadata,
        ]);

        Log::warning('Security alert created', [
            'alert_id' => $alert->id,
            'type' => $type,
            'severity' => $severity,
            'admin_id' => $adminId,
            'ip_address' => $ipAddress,
            'target_user' => $metadata['username'] ?? ($metadata['user_id'] ?? null),
        ]);

        self::sendAlertEmail($alert);

        return $alert;
    }

    public static function sendTransactionNotice(array $details): void
    {
        $recipient = config('security.alert_email');
        $from = config('mail.from.address');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Admin transaction email skipped because mail addresses are not configured.');
            return;
        }

        $userId = $details['user_id'] ?? null;
        $username = $details['username'] ?? null;
        $fullName = $details['user_name'] ?? null;
        $userEmail = $details['user_email'] ?? null;

        // Auto-resolve user info from database if missing
        if ($userId && (! $username || ! $fullName || ! $userEmail)) {
            try {
                $user = User::with('profile')->find($userId);
                if ($user) {
                    $username = $username ?: ($user->profile?->username ?? $user->username ?? "user#{$userId}");
                    $fullName = $fullName ?: trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: ($user->name ?? $username);
                    $userEmail = $userEmail ?: $user->email;
                }
            } catch (\Throwable $e) {
                Log::debug('Failed to resolve user details for admin transaction notice', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }

        $displayUsername = $username ? '@' . ltrim($username, '@') : ($userId ? 'User #' . $userId : 'N/A');
        $userUrl = $username
            ? url('/adminoffice/user/' . ltrim($username, '@'))
            : ($userId ? url('/adminoffice/user/' . $userId) : null);

        $action = ucfirst((string) ($details['action'] ?? 'Adjustment'));
        $currency = strtoupper((string) ($details['currency'] ?? 'NGN'));
        $amountFormatted = isset($details['amount']) ? number_format((float) $details['amount'], 2) : '0.00';
        $adminInfo = $details['admin_username'] ?? (! empty($details['admin_id']) ? 'Admin #' . $details['admin_id'] : 'Authorized Admin');
        $reference = $details['reference'] ?? 'N/A';
        $timeStr = ! empty($details['time'])
            ? (is_string($details['time']) ? $details['time'] : (is_object($details['time']) && method_exists($details['time'], 'format') ? $details['time']->format('Y-m-d H:i:s') : (string) $details['time']))
            : now()->format('Y-m-d H:i:s');

        $mailData = [
            'action' => $action,
            'currency' => $currency,
            'amount' => $amountFormatted,
            'user_id' => $userId,
            'username' => $username,
            'display_username' => $displayUsername,
            'user_name' => $fullName,
            'user_email' => $userEmail,
            'user_url' => $userUrl,
            'admin_info' => $adminInfo,
            'reference' => $reference,
            'time' => $timeStr,
        ];

        $subject = "[Dupay Admin] {$action} Notice: {$currency} {$amountFormatted} for {$displayUsername}";

        try {
            Mail::send('emails.admin_transaction_notice', $mailData, function ($mail) use ($recipient, $subject) {
                $mail->to($recipient)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::warning('Admin transaction HTML email failed, trying raw text fallback', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            try {
                $rawBody = "=== DUPAY ADMIN WALLET NOTIFICATION ===\n\n" .
                    "Action:       {$action}\n" .
                    "Amount:       {$currency} {$amountFormatted}\n" .
                    "Target User:  {$displayUsername}" . ($fullName ? " ({$fullName})" : "") . "\n" .
                    "User Email:   " . ($userEmail ?? 'N/A') . "\n" .
                    "User ID:      " . ($userId ?? 'N/A') . "\n" .
                    ($userUrl ? "User Link:    {$userUrl}\n" : "") .
                    "Initiator:    {$adminInfo}\n" .
                    "Reference:    {$reference}\n" .
                    "Time:         {$timeStr}\n\n" .
                    "Please review this transaction in the Dupay admin office.";

                Mail::raw($rawBody, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient)->subject($subject);
                });
            } catch (\Throwable $rawException) {
                Log::warning('Admin transaction raw email failed', ['exception' => get_class($rawException)]);
            }
        }
    }

    private static function sendAlertEmail(SecurityAlert $alert): void
    {
        $recipient = config('security.alert_email');
        $from = config('mail.from.address');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Security alert email skipped because mail addresses are not configured.');
            return;
        }

        $metadata = is_array($alert->metadata) ? $alert->metadata : [];
        $userId = $metadata['user_id'] ?? null;
        $username = $metadata['username'] ?? null;
        $fullName = $metadata['user_name'] ?? null;
        $userEmail = $metadata['user_email'] ?? null;

        // Auto-resolve user details if user_id is in metadata
        if ($userId && (! $username || ! $fullName)) {
            try {
                $user = User::with('profile')->find($userId);
                if ($user) {
                    $username = $username ?: ($user->profile?->username ?? $user->username ?? "user#{$userId}");
                    $fullName = $fullName ?: trim(($user->profile?->first_name ?? '') . ' ' . ($user->profile?->last_name ?? '')) ?: ($user->name ?? $username);
                    $userEmail = $userEmail ?: $user->email;
                }
            } catch (\Throwable $e) {
                // Ignore lookup errors
            }
        }

        $displayUsername = $username ? '@' . ltrim($username, '@') : ($userId ? 'User #' . $userId : null);
        $userUrl = $username
            ? url('/adminoffice/user/' . ltrim($username, '@'))
            : ($userId ? url('/adminoffice/user/' . $userId) : null);

        $severityUpper = strtoupper($alert->severity);
        $timeStr = $alert->created_at ? $alert->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');

        $mailData = [
            'alert' => $alert,
            'severity' => $severityUpper,
            'title' => $alert->title,
            'message_text' => $alert->message,
            'type' => $alert->type,
            'admin_id' => $alert->admin_id,
            'ip_address' => $alert->ip_address,
            'user_id' => $userId,
            'username' => $username,
            'display_username' => $displayUsername,
            'user_name' => $fullName,
            'user_email' => $userEmail,
            'user_url' => $userUrl,
            'metadata' => $metadata,
            'time' => $timeStr,
        ];

        $subject = "[Dupay Security Alert - {$severityUpper}] {$alert->title}";
        if ($displayUsername) {
            $subject .= " ({$displayUsername})";
        }

        try {
            Mail::send('emails.admin_security_alert', $mailData, function ($mail) use ($recipient, $subject) {
                $mail->to($recipient)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::warning('Security alert HTML email failed, trying raw fallback', [
                'alert_id' => $alert->id,
                'exception' => get_class($exception),
            ]);

            try {
                $userSection = '';
                if ($displayUsername) {
                    $userSection = "Target User:  {$displayUsername}" . ($fullName ? " ({$fullName})" : "") . "\n" .
                        ($userEmail ? "User Email:   {$userEmail}\n" : "") .
                        ($userUrl ? "User Profile: {$userUrl}\n" : "");
                }

                $rawBody = "=== DUPAY SECURITY ALERT: {$severityUpper} ===\n\n" .
                    "Title:        {$alert->title}\n" .
                    "Type:         {$alert->type}\n" .
                    "Severity:     {$severityUpper}\n" .
                    "Message:      {$alert->message}\n\n" .
                    $userSection .
                    "Admin ID:     " . ($alert->admin_id ?? 'System / Guest') . "\n" .
                    "IP Address:   " . ($alert->ip_address ?? 'Unknown') . "\n" .
                    "Time:         {$timeStr}\n\n" .
                    "View all alerts: " . url('/adminoffice/security-alerts');

                Mail::raw($rawBody, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient)->subject($subject);
                });
            } catch (\Throwable $rawException) {
                Log::warning('Security alert raw email failed', ['alert_id' => $alert->id, 'exception' => get_class($rawException)]);
            }
        }
    }
}
