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
        'suggested_questions',
        'embedding',
        'slots',
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
            'suggested_questions' => 'array',
            'embedding' => 'array',
            'slots' => 'array',
            'center' => 'array',
            'zoom' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
