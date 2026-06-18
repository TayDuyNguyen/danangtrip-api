<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RefundRequest extends Model
{
    protected $fillable = [
        'refund_code',
        'booking_id',
        'payment_id',
        'reason_type',
        'requested_amount',
        'approved_amount',
        'refund_percent',
        'status',
        'bank_code',
        'account_no',
        'account_name',
        'policy_snapshot',
        'reason',
        'requested_by',
        'completed_by',
        'transfer_reference',
        'evidence_url',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'refund_percent' => 'decimal:2',
            'account_no' => 'encrypted',
            'policy_snapshot' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected $hidden = ['account_no'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
