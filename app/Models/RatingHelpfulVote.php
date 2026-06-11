<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RatingHelpfulVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'rating_id',
        'user_id',
    ];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
