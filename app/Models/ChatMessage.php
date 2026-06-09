<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChatMessage extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'question',
        'answer',
        'intent',
        'is_in_scope',
        'tokens_used',
        'provider',
        'model',
        'cache_hit',
        'context',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_in_scope' => 'boolean',
            'tokens_used' => 'integer',
            'cache_hit' => 'boolean',
            'context' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
