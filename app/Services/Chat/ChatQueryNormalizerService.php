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
     * Chuẩn hóa toàn bộ mảng kết quả phân tích sau khi qua bộ NLU.
     *
     * @param array<string,mixed> $understanding Kết quả phân tích ban đầu
     * @return array<string,mixed> Kết quả phân tích đã chuẩn hóa
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
     * Tìm kiếm và giải quyết destination_id từ tên điểm đến trong cơ sở dữ liệu.
     * Cơ chế tìm kiếm ưu tiên khớp chính xác trước, sau đó khớp một phần.
     *
     * @param array<string,mixed> $understanding Kết quả phân tích hiện tại
     * @return array<string,mixed> Kết quả phân tích sau khi đã giải quyết ID địa điểm
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
     * Bổ sung loại nội dung (content_types) từ các gợi ý nếu AI NLU chưa điền.
     *
     * @param array<string,mixed> $understanding Kết quả phân tích hiện tại
     * @return array<string,mixed> Kết quả phân tích sau khi giải quyết loại nội dung
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
     * Bổ sung chủ đề (topics) từ gợi ý của bộ lọc rule-based nếu AI NLU chưa điền.
     *
     * @param array<string,mixed> $understanding Kết quả phân tích hiện tại
     * @return array<string,mixed> Kết quả phân tích sau khi giải quyết chủ đề
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
