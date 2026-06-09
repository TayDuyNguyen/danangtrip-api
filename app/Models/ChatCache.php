<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatCache extends Model
{
    protected $table = 'chat_cache';

    protected $fillable = [
        'question_hash',
        'normalized_question',
        'locale',
        'intent',
        'answer',
        'recommendations',
        'center',
        'zoom',
        'provider',
        'model',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'recommendations' => 'array',
            'center' => 'array',
            'zoom' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
