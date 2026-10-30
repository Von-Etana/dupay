<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SecurityAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(Admin::class, 'acknowledged_by');
    }

    /**
     * Get target user ID from alert metadata if available.
     */
    public function getTargetUserIdAttribute(): ?int
    {
        return isset($this->metadata['user_id']) ? (int) $this->metadata['user_id'] : null;
    }

    /**
     * Get target username from metadata, or resolve from database cache if only user_id is present.
     */
    public function getTargetUsernameAttribute(): ?string
    {
        if (! empty($this->metadata['username'])) {
            return $this->metadata['username'];
        }

        if ($this->target_user_id) {
            return Cache::remember('sec_alert_uname_' . $this->target_user_id, 300, function () {
                $user = User::with('profile')->find($this->target_user_id);
                return $user?->profile?->username ?? $user?->username ?? null;
            });
        }

        return null;
    }

    /**
     * Get target user's full name from metadata or resolve from database.
     */
    public function getTargetNameAttribute(): ?string
    {
        if (! empty($this->metadata['user_name'])) {
            return $this->metadata['user_name'];
        }

        if ($this->target_user_id) {
            return Cache::remember('sec_alert_name_' . $this->target_user_id, 300, function () {
                $user = User::with('profile')->find($this->target_user_id);
                return trim(($user?->profile?->first_name ?? '') . ' ' . ($user?->profile?->last_name ?? '')) ?: ($user?->name ?? null);
            });
        }

        return null;
    }

    /**
     * Get target user's email from metadata or resolve from database.
     */
    public function getTargetEmailAttribute(): ?string
    {
        if (! empty($this->metadata['user_email'])) {
            return $this->metadata['user_email'];
        }

        if ($this->target_user_id) {
            return Cache::remember('sec_alert_email_' . $this->target_user_id, 300, function () {
                $user = User::find($this->target_user_id);
                return $user?->email;
            });
        }

        return null;
    }

    /**
     * Get direct admin URL to target user's profile.
     */
    public function getTargetUserUrlAttribute(): ?string
    {
        $identifier = $this->target_username ?: $this->target_user_id;
        return $identifier ? url('adminoffice/user/' . ltrim($identifier, '@')) : null;
    }
}
