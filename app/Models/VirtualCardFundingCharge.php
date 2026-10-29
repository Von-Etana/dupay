<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardFundingCharge extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'currency',
        'minimum_amount',
        'maximum_amount',
        'charge',
    ];
}
