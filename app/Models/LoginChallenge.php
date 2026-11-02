<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginChallenge extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['id', 'otp_hash', 'password_fingerprint', 'device_hash'];
    protected $casts = ['expires_at' => 'datetime', 'otp_expires_at' => 'datetime', 'sent_at' => 'datetime', 'consumed_at' => 'datetime'];
}
