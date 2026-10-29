<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardExchangeRate extends Model
{
    use HasFactory;
    protected $fillable = [
        'currency',
        'rate',
        'fee',
        'cap',
    ];
}