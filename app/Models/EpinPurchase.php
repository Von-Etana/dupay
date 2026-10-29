<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpinPurchase extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'network_id',
        'value',
        'quantity',
        'cards',
        'reference',
        'pdf_path',
    ];
}
