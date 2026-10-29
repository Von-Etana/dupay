<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardNGN extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'card_id',
        'card_brand',
        'card_name',
        'card_number',
        'cvv',
        'expiry',
        'card_type',
        'balance',
        'billing_address',
        'billing_city',
        'billing_state',
        'response_data',
        'status',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
