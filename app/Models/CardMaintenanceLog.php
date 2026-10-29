<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardMaintenanceLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'card_id',
        'reference',
        'amount',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(PhysicalCard::class, 'card_id');
    }
}
