<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardDispute extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount'       => 'decimal:2',
        'submitted_at' => 'datetime',
        'resolved_at'  => 'datetime',
    ];

    /**
     * Reasons accepted by StroWallet's naira_CreateDispute endpoint.
     * Keys are sent to the API verbatim; values are what the admin sees.
     */
    public const REASONS = [
        'fraudulent'                 => 'Fraudulent transaction',
        'not_received'               => 'Cash / goods not received',
        'duplicate'                  => 'Duplicate charge',
        'canceled'                   => 'Transaction was cancelled',
        'product_not_as_described'   => 'Product not as described',
        'service_not_as_described'   => 'Service not as described',
        'other'                      => 'Other',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function physicalCard()
    {
        return $this->belongsTo(PhysicalCard::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst(str_replace('_', ' ', (string) $this->reason));
    }

    public function getStatusBadgeAttribute(): string
    {
        $class = match ($this->status) {
            'submitted' => 'badge bg-info',
            'won'       => 'badge bg-success',
            'lost'      => 'badge bg-danger',
            'failed'    => 'badge bg-danger',
            'withdrawn' => 'badge bg-secondary',
            default     => 'badge bg-warning text-dark', // pending
        };

        return '<span class="' . $class . '">' . ucfirst((string) $this->status) . '</span>';
    }
}
