<?php

namespace App\Models;

use App\Enums\TourScheduleBookingAvailability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TourSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'start_date',
        'end_date',
        'max_people',
        'booked_people',
        'price_adult',
        'price_child',
        'price_infant',
        'status', // available, cancelled
        'booking_availability', // open, sold_out
        'departure_code',
        'departure_place',
        'booking_deadline',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'max_people' => 'integer',
            'booked_people' => 'integer',
            'price_adult' => 'decimal:0',
            'price_child' => 'decimal:0',
            'price_infant' => 'decimal:0',
            'booking_availability' => TourScheduleBookingAvailability::class,
            'booking_deadline' => 'datetime',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
