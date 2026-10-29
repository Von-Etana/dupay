<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardWebhook extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'transaction_reference',
        'card_id',
        'amount',
        'type',
        'currency',
        'authorization_raw_payload',
        'success_raw_payload',
        'declined_raw_payload',
        'status',
        'tranx_status',
    ];
}
