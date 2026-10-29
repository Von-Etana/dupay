<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatSetting extends Model
{
    use HasFactory;
    
    protected $fillable = ['vat_value', 'type'];
}
