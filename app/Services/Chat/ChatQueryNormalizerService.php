<?php

namespace App\Services\Chat;

use App\Models\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Query Normalization Service
 *
 * Map text entities từ AI NLU/Rule-based sang DB identifiers:
 *   - destination name → location_id
 *   - content_type_hints → content_types (nếu AI NLU chưa cung cấp)
 *   - topic_hints → topics (nếu AI NLU chưa cung cấp)
 */
final class ChatQueryNormalizerService
{
    /**
     * Chuẩn hóa toàn bộ understanding array sau NLU.
     *
     * @param  array<string,mixed>  $understanding
     * @return array<string,mixed>
     */
    public function normalize(array $understanding): array
    {
        // 1. Map destination name → location_id
        $understanding = $this->resolveDestinationId($understanding);

        // 2. Fallback content_types từ hints nếu AI NLU chưa set
        $understanding = $this->resolveContentTypes($understanding);

        // 3. Fallback topics từ hints nếu AI NLU chưa set
        $understanding = $this->resolveTopics($understanding);

        // 4. Ensure keywords là array
        if (! isset($understanding['keywords']) || ! is_array($understanding['keywords'])) {
            $understanding['keywords'] = [];
        }

        return $understanding;
    }

    /**
     * Lookup location_id từ destination name trong DB.
     * Ưu tiên: exact match → partial match (LIKE).
     *
     * @param  array<string,mixed>  $understanding
     * @return array<string,mixed>
     */
    private function resolveDestinationId(array $understanding): array
    {
        $destinationName = (string) ($understanding['destination'] ?? '');
        if ($destinationName === '') {
            return $understanding;
        }

        $cacheKey = 'chatbot:dest_id:'.md5(mb_strtolower($destinationName));

        try {
            $locationId = Cache::remember($cacheKey, 3600, function () use ($destinationName): ?int {
                // 1. Exact match (case-insensitive)
                $id = Location::query()
                    ->where('status', 'active')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($destinationName)])
                    ->value('id');

                if ($id !== null) {
                    return (int) $id;
                }

                // 2. Partial match
                $id = Location::query()
                    ->where('status', 'active')
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($destinationName).'%'])
                    ->orderByDesc('is_featured')
                    ->value('id');

                return $id !== null ? (int) $id : null;
            });

            if ($locationId !== null) {
                $understanding['destination_id'] = $locationId;
            }
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_NORMALIZER_DEST_LOOKUP_FAILED', [
                'destination' => $destinationName,
                'message' => $e->getMessage(),
            ]);
        }

        return $understanding;
    }

    /**
     * Nếu AI NLU chưa set content_types, dùng content_type_hints từ rule-based.
     *
     * @param  array<string,mixed>  $understanding
     * @return array<string,mixed>
     */
    private function resolveContentTypes(array $understanding): array
    {
        $contentTypes = (array) ($understanding['content_types'] ?? []);

        if (! empty($contentTypes)) {
            return $understanding;
        }

        // Fallback từ rule-based hints
        $hints = (array) ($understanding['content_type_hints'] ?? []);
        if (! empty($hints)) {
            $understanding['content_types'] = $hints;
        }

        return $understanding;
    }

    /**
     * Nếu AI NLU chưa set topics, dùng topic_hints từ rule-based.
     *
     * @param  array<string,mixed>  $understanding
     * @return array<string,mixed>
     */
    private function resolveTopics(array $understanding): array
    {
        $topics = (array) ($understanding['topics'] ?? []);

        if (! empty($topics)) {
            return $understanding;
        }

        // Fallback từ rule-based hints
        $hints = (array) ($understanding['topic_hints'] ?? []);
        if (! empty($hints)) {
            $understanding['topics'] = $hints;
        }

        // Map location_topic → topics
        $locationTopic = (string) ($understanding['location_topic'] ?? '');
        if ($locationTopic !== '' && ! in_array($locationTopic, $understanding['topics'] ?? [], true)) {
            $understanding['topics'][] = $locationTopic;
        }

        return $understanding;
    }
}
