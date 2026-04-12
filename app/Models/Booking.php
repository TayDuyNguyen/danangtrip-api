<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_note',
        'total_amount',
        'discount_amount',
        'final_amount',
        'deposit_amount',
        'payment_method',
        'payment_status',
        'booking_status',
        'cancellation_reason',
        'booked_at',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'booked_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
