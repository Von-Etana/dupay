<?php

namespace App\Models\Fintech;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ForeignAccount;

class ForeignAccountTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'foreign_account_id',
        'type',
        'kind',
        'amount',
        'currency',
        'description',
        'status',
        'balance_before',
        'balance_after',
        'payer_name',
        'payer_bank_name',
        'payer_account_number',
        'payer_routing_number',
        'fee',
        'amount_settled',
        'reference_id',
    ];

    public function foreignAccount()
    {
        return $this->belongsTo(ForeignAccount::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }
}
