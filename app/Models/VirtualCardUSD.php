<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardUSD extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'card_id',
        'card_brand',
        'card_name',
        'card_number',
        'card_type',
        'cvv',
        'expiry',
        'balance',
        'billing_address',
        'billing_city',
        'billing_state',
        'reference',
        'response_data',
        'freeze_status',
        'status',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
