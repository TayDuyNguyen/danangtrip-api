<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'action_key',
        'points',
        'balance_after',
        'source_type',
        'source_id',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'points' => 'integer',
            'balance_after' => 'integer',
            'source_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
