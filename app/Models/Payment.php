<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'transaction_code',
        'amount',
        'payment_method',
        'payment_status',
        'payment_gateway',
        'gateway_response',
        'paid_at',
        'refunded_at',
        'refund_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'gateway_response' => 'json',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
