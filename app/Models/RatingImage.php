<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RatingImage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'rating_id',
        'image_url',
        'sort_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }
}
