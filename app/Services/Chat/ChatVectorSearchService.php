<?php

namespace App\Services\Chat;

use App\Models\ChatKnowledgeBase;
use Illuminate\Support\Collection;

final class ChatVectorSearchService
{
    public function __construct(
        private readonly ChatEmbeddingService $embeddingService
    ) {}

    /** @return Collection<int,ChatKnowledgeBase> */
    public function search(string $question, string $intent, int $limit): Collection
    {
        if (! (bool) config('chatbot.vector_enabled', false)) {
            return collect();
        }

        $types = $this->typesForIntent($intent);
        $candidateLimit = max($limit * 4, (int) config('chatbot.vector_candidate_limit', 80));
        $minSimilarity = (float) config('chatbot.vector_min_similarity', 0.68);

        $baseQuery = ChatKnowledgeBase::query()
            ->where('is_active', true)
            ->whereNotNull('embedding')
            ->when($types !== [], fn ($query) => $query->whereIn('type', $types));

        if (! (clone $baseQuery)->exists()) {
            return collect();
        }

        $embedding = $this->embeddingService->embed($question, 'RETRIEVAL_QUERY');
        if (! $embedding || empty($embedding['values'])) {
            return collect();
        }

        $queryVector = $embedding['values'];

        return $baseQuery
            ->get()
            ->map(function (ChatKnowledgeBase $item) use ($queryVector): array {
                return [
                    'item' => $item,
                    'score' => $this->cosineSimilarity($queryVector, (array) $item->embedding),
                ];
            })
            ->filter(fn (array $result): bool => $result['score'] >= $minSimilarity)
            ->sortByDesc('score')
            ->take($limit)
            ->map(function (array $result) {
                $result['item']->similarity_score = $result['score'];
                return $result['item'];
            })
            ->values();
    }

    /** @return array<int,string> */
    private function typesForIntent(string $intent): array
    {
        return match ($intent) {
            'tour', 'booking', 'schedule' => ['tour', 'blog', 'policy'],
            'location', 'food', 'hotel' => ['location', 'blog'],
            'blog' => ['blog'],
            'payment', 'refund', 'account', 'contact', 'loyalty' => ['policy', 'blog'],
            'greeting' => ['tour', 'location', 'blog', 'policy'],
            default => ['tour', 'location', 'blog', 'policy'],
        };
    }

    /** @param array<int,float|int|string> $a @param array<int,float|int|string> $b */
    private function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $left = (float) $a[$index];
            $right = (float) $b[$index];
            $dot += $left * $right;
            $normA += $left * $left;
            $normB += $right * $right;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
