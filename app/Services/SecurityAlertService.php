<?php

namespace App\Services;

use App\Models\SecurityAlert;
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

        try {
            Mail::raw(
                "An admin wallet transaction was completed.\n\n" .
                "Action: {$details['action']}\n" .
                "Currency: {$details['currency']}\n" .
                "Amount: {$details['amount']}\n" .
                "User ID: {$details['user_id']}\n" .
                "Reference: {$details['reference']}\n" .
                "Time: {$details['time']}",
                function ($mail) use ($recipient) {
                    $mail->to($recipient)->subject('Dupay admin transaction notification');
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('Admin transaction email failed', ['exception' => get_class($exception)]);
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

        try {
            Mail::raw(
                "{$alert->title}\n\n" .
                "Severity: " . strtoupper($alert->severity) . "\n" .
                "Type: {$alert->type}\n" .
                "Message: {$alert->message}\n" .
                "Admin ID: " . ($alert->admin_id ?? 'Unknown') . "\n" .
                "IP address: " . ($alert->ip_address ?? 'Unknown') . "\n" .
                "Time: {$alert->created_at}",
                function ($mail) use ($recipient, $alert) {
                    $mail->to($recipient)->subject('Dupay security alert: ' . strtoupper($alert->severity));
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('Security alert email failed', ['alert_id' => $alert->id, 'exception' => get_class($exception)]);
        }
    }
}
