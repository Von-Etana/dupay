<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardTopup extends Model
{
    use HasFactory;
    protected $fillable = [
        'card_id',
        'customer_id',
        'currency',
        'prev_balance',
        'balance',
        'ref_id',
        'trans_id',
        'action',
        'transaction_time',
    ];
}
