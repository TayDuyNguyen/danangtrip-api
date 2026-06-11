<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserPointBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'available_points',
        'lifetime_earned',
        'lifetime_spent',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'available_points' => 'integer',
            'lifetime_earned' => 'integer',
            'lifetime_spent' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
