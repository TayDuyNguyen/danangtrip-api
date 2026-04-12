<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'subcategory_id',
        'description',
        'short_description',
        'address',
        'district',
        'ward',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'opening_hours',
        'price_min',
        'price_max',
        'price_level',
        'avg_rating',
        'review_count',
        'view_count',
        'favorite_count',
        'thumbnail',
        'images',
        'video_url',
        'status',
        'is_featured',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'subcategory_id' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'opening_hours' => 'array',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'price_level' => 'integer',
            'avg_rating' => 'decimal:2',
            'review_count' => 'integer',
            'view_count' => 'integer',
            'favorite_count' => 'integer',
            'images' => 'array',
            'is_featured' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'location_tags')
            ->withPivot('created_at');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function approvedRatings(): HasMany
    {
        return $this->hasMany(Rating::class)->where('status', 'approved');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'location_amenities')
            ->withPivot('created_at');
    }
}
