<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampDutySetting extends Model
{
    use HasFactory;
    
    protected $table = "stamp_duty_settings";
    
    protected $fillable = [
        'charge',
        'start',
    ];
}
