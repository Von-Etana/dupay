<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeCardPin extends Model
{
    protected $fillable = [
        'network_id', 'value', 'pin', 'serial', 'status', 'sold_to',
        'sold_reference', 'sold_at',
    ];

    protected $casts = ['value' => 'decimal:2', 'sold_at' => 'datetime'];

    public function network()
    {
        return $this->belongsTo(\App\Models\Telecom\Network::class, 'network_id');
    }
}
