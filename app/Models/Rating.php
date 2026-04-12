<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'location_id',
        'tour_id',
        'booking_id',
        'score',
        'comment',
        'image_count',
        'status',
        'rejected_reason',
        'approved_by',
        'approved_at',
        'helpful_count',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'image_count' => 'integer',
            'helpful_count' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RatingImage::class);
    }
}
