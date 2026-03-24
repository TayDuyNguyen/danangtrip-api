<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Class SearchService
 * Handles business logic related to search.
 * (Xử lý logic nghiệp vụ liên quan đến tìm kiếm)
 */
final class SearchService
{
    /**
     * SearchService constructor.
     * (Khởi tạo SearchService)
     *
     * @return void
     */
    public function __construct(
        protected LocationRepositoryInterface $locationRepository,
        protected SearchLogRepositoryInterface $searchLogRepository
    ) {}

    /**
     * Search locations by query and filters.
     * (Tìm kiếm địa điểm theo từ khóa và bộ lọc)
     */
    public function search(array $data, Request $request): array
    {
        try {
            $q = trim((string) ($data['q'] ?? ''));
            $filters = $data;
            unset($filters['q']);

            $paginator = $this->locationRepository->getLocations(array_merge($filters, ['q' => $q]));

            $this->searchLogRepository->logSearch([
                'user_id' => $request->user()?->id,
                'session_id' => $this->resolveSessionId($request, $data['session_id'] ?? null),
                'query' => $q,
                'results_count' => $this->resolveResultsCount($paginator),
                'filters' => $this->normalizeFiltersForLog($filters),
                'created_at' => now(),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'query' => $q,
                    'results' => $paginator,
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to search locations',
            ];
        }
    }

    /**
     * Get search suggestions by query prefix.
     * (Lấy gợi ý tìm kiếm theo tiền tố)
     */
    public function suggestions(array $data): array
    {
        try {
            $q = trim((string) ($data['q'] ?? ''));
            $limit = (int) ($data['limit'] ?? 5);

            $fromLocations = $this->locationRepository->getNameSuggestions($q, $limit);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'query' => $q,
                    'suggestions' => $fromLocations,
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get suggestions',
            ];
        }
    }

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     */
    public function popular(array $data): array
    {
        try {
            $limit = (int) ($data['limit'] ?? 10);
            $days = (int) ($data['days'] ?? 30);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'popular' => $this->searchLogRepository->getPopularQueries($limit, $days),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get popular queries',
            ];
        }
    }

    /**
     * Resolve session ID from request.
     * (Xác định ID phiên từ request)
     */
    private function resolveSessionId(Request $request, ?string $sessionId): string
    {
        $sessionId = trim((string) $sessionId);
        if ($sessionId !== '') {
            return mb_substr($sessionId, 0, 100);
        }

        $header = trim((string) $request->header('X-Session-Id'));
        if ($header !== '') {
            return mb_substr($header, 0, 100);
        }

        $raw = (string) $request->ip().'|'.(string) $request->userAgent();

        return substr(hash('sha256', $raw), 0, 100);
    }

    /**
     * Resolve results count from paginator.
     * (Xác định số lượng kết quả từ paginator)
     */
    private function resolveResultsCount(LengthAwarePaginator $paginator): int
    {
        return (int) $paginator->total();
    }

    /**
     * Normalize filters for logging.
     * (Chuẩn hóa bộ lọc để ghi log)
     */
    private function normalizeFiltersForLog(array $filters): array
    {
        $filtersForLog = $filters;
        unset($filtersForLog['session_id']);

        return $filtersForLog;
    }
}
