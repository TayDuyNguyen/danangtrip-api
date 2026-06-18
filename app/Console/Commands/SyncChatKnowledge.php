<?php

namespace App\Console\Commands;

use App\Models\ChatCache;
use App\Models\ChatKnowledgeBase;
use App\Services\Chat\ChatEmbeddingService;
use App\Services\Chat\ChatKnowledgeSyncService;
use App\Support\BooleanColumn;
use Illuminate\Console\Command;

final class SyncChatKnowledge extends Command
{
    protected $signature = 'chatbot:sync-knowledge
        {--embed : Generate missing embeddings after syncing knowledge}
        {--force : Regenerate embeddings even when an embedding already exists}
        {--limit= : Maximum number of knowledge items to embed in this run}';

    protected $description = 'Sync chatbot knowledge base from real database records and optionally generate embeddings.';

    public function handle(
        ChatKnowledgeSyncService $knowledgeSync,
        ChatEmbeddingService $embeddingService
    ): int {
        $this->info('Syncing chatbot knowledge from tours, locations, blogs and policies...');
        $counts = $knowledgeSync->syncAll();

        $this->table(
            ['Type', 'Synced'],
            collect($counts)->map(fn (int $count, string $type): array => [$type, $count])->values()->all()
        );

        ChatCache::query()->delete();
        $this->line('Chat cache cleared.');

        if (! $this->option('embed')) {
            $this->warn('Embedding generation skipped. Run with --embed to enable Vector RAG data.');

            return self::SUCCESS;
        }

        $query = ChatKnowledgeBase::query()
            ->tap(fn ($builder) => BooleanColumn::where($builder, 'is_active', true))
            ->orderBy('id');

        if (! $this->option('force')) {
            $query->whereNull('embedding');
        }

        $limit = $this->option('limit');
        if (is_numeric($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->info('No knowledge items need embedding.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $embedded = 0;
        $failed = 0;

        foreach ($items as $item) {
            $text = trim($item->title."\n\n".$item->content);
            $embedding = $embeddingService->embed($text, 'RETRIEVAL_DOCUMENT');

            if ($embedding === null) {
                $failed++;
                $bar->advance();

                continue;
            }

            $item->forceFill([
                'embedding' => $embedding['values'],
                'embedding_model' => $embedding['provider'].':'.$embedding['model'],
                'embedding_dimension' => count($embedding['values']),
                'last_embedded_at' => now(),
            ])->save();

            $embedded++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Embedded {$embedded} item(s).");

        if ($failed > 0) {
            $this->warn("Failed to embed {$failed} item(s). Check logs for provider/key errors.");
        }

        return $failed > 0 && $embedded === 0 ? self::FAILURE : self::SUCCESS;
    }
}
