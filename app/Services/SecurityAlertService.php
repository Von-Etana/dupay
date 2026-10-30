<?php

namespace App\Services;

use App\Models\SecurityAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        return $alert;
    }
}
