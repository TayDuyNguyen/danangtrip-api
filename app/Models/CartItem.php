<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tour_id',
        'tour_schedule_id',
        'quantity_adult',
        'quantity_child',
        'quantity_infant',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tour_id' => 'integer',
            'tour_schedule_id' => 'integer',
            'quantity_adult' => 'integer',
            'quantity_child' => 'integer',
            'quantity_infant' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
