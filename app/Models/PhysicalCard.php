<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalCard extends Model
{
    use HasFactory;
    
    
    protected $fillable = [
    'user_id',
    'card_id',
    'account_id',
    'currency',
    'card_hash',
    'card_pan',
    'cvv',
    'send_to',
    'bin_check_name',
    'setup_step',

    'customer_id',
    'type',
    'brand',
    'masked_pan',
    'expiry_month',
    'expiry_year',
    'status',

    'atm',
    'pos',
    'web',
    'mobile',

    'spending_limit_amount',
    'spending_limit_interval',
    'pin',
    'shipping_details',
];




public function user()
{
    return $this->belongsTo(User::class);
}


}
