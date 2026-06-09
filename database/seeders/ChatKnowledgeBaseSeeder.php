<?php

namespace Database\Seeders;

use App\Services\Chat\ChatKnowledgeSyncService;
use Illuminate\Database\Seeder;

class ChatKnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        app(ChatKnowledgeSyncService::class)->syncAll();
    }
}
