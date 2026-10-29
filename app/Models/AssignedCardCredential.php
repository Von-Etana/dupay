<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedCardCredential extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'card_pan_last6', 'cvv', 'used', 'card_pan', 'brand', 'delivery_status'];
    
    protected $hidden = [
        'cvv',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
