<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositFee extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'currency',
        'minimum_amount',
        'maximum_amount',
        'fee',
        'charge_type',
        'cap',
    ];
}