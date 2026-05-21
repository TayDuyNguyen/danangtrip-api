<?php

namespace App\Models;

use App\Enums\TourBookingAvailability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tour_category_id',
        'description',
        'short_desc',
        'itinerary',
        'inclusions',
        'exclusions',
        'price_adult',
        'price_child',
        'price_infant',
        'discount_percent',
        'duration',
        'start_time',
        'meeting_point',
        'max_people',
        'min_people',
        'available_from',
        'available_to',
        'thumbnail',
        'images',
        'video_url',
        'status', // active, inactive (booking capacity: booking_availability)
        'is_featured',
        'is_hot',
        'view_count',
        'booking_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'itinerary' => 'json',
            'inclusions' => 'json',
            'exclusions' => 'json',
            'images' => 'json',
            'price_adult' => 'decimal:2',
            'price_child' => 'decimal:2',
            'price_infant' => 'decimal:2',
            'discount_percent' => 'integer',
            'max_people' => 'integer',
            'min_people' => 'integer',
            'available_from' => 'date',
            'available_to' => 'date',
            'is_featured' => 'boolean',
            'is_hot' => 'boolean',
            'view_count' => 'integer',
            'booking_count' => 'integer',
            'booking_availability' => TourBookingAvailability::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class, 'tour_category_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TourSchedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'tour_locations')
            ->withPivot('created_at');
    }
}
