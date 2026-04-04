<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'tour_id',
        'tour_schedule_id',
        'item_type',
        'item_name',
        'travel_date',
        'quantity_adult',
        'quantity_child',
        'quantity_infant',
        'unit_price_adult',
        'unit_price_child',
        'unit_price_infant',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'quantity_adult' => 'integer',
            'quantity_child' => 'integer',
            'quantity_infant' => 'integer',
            'unit_price_adult' => 'decimal:0',
            'unit_price_child' => 'decimal:0',
            'unit_price_infant' => 'decimal:0',
            'subtotal' => 'decimal:0',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function tourSchedule(): BelongsTo
    {
        return $this->belongsTo(TourSchedule::class);
    }
}
