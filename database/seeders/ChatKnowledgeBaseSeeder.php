<?php

namespace Database\Seeders;

use App\Services\Chat\ChatKnowledgeSyncService;
use Database\Seeders\Concerns\ImportsSeederSql;
use Illuminate\Database\Seeder;

class ChatKnowledgeBaseSeeder extends Seeder
{
    use ImportsSeederSql;

    public function run(): void
    {
        try {
            $this->importSeederSql('seeders_v2/demo/09_chat_knowledge.sql');
        } catch (\Throwable $e) {
            // Fallback to online sync if SQL file is missing
            app(ChatKnowledgeSyncService::class)->syncAll();
        }
    }
}
