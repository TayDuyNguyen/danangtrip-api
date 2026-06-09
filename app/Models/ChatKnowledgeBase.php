<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatKnowledgeBase extends Model
{
    protected $table = 'chat_knowledge_base';

    protected $fillable = [
        'type',
        'title',
        'content',
        'reference_id',
        'reference_slug',
        'metadata',
        'embedding',
        'embedding_model',
        'embedding_dimension',
        'content_hash',
        'last_embedded_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'embedding' => 'array',
            'embedding_dimension' => 'integer',
            'last_embedded_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
