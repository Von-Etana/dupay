<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForeignAccount extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'account_id',
        'holder_id',
        'account_number',
        'account_name',
        'bank_name',
        'bank_code',
        'routing_number',
        'bank_address',
        'balance',
        'currency',
        'status',
        'bank_data',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function foreignTransactions()
    {
        return $this->hasMany(Fintech\ForeignAccountTransaction::class);
    }
}
