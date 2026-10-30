<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
