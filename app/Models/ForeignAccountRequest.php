<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForeignAccountRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'bvn',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'postal_code',
        'employment_status',
        'occupation',
        'primary_purpose',
        'source_of_funds',
        'expected_monthly_inflow',
        'document',
        'document_type',
        'document_issue_date',
        'document_expiry_date',
        'document2',
        'document2_type',
        'document2_issue_date',
        'document2_expiry_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
