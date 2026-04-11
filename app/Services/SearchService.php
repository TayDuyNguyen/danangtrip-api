<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\ViewRepositoryInterface;
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
     */
    public function __construct(
        protected LocationRepositoryInterface $locationRepository,
        protected TourRepositoryInterface $tourRepository,
        protected SearchLogRepositoryInterface $searchLogRepository,
        protected ViewRepositoryInterface $viewRepository,
        protected FavoriteRepositoryInterface $favoriteRepository,
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Search locations or tours by query and filters.
     * (Tìm kiếm địa điểm hoặc tour theo từ khóa và bộ lọc)
     */
    public function search(array $data, Request $request): array
    {
        try {
            $q = trim((string) ($data['q'] ?? ''));
            $type = $data['type'] ?? 'location';

            if ($type === 'tour') {
                $paginator = $this->tourRepository->getTours($this->mapTourFilters($data, $q));
                $logFilters = array_merge($this->mapTourFilters($data, $q), ['type' => $type]);
            } else {
                $paginator = $this->locationRepository->getLocations($this->mapLocationFilters($data, $q));
                $logFilters = array_merge($this->mapLocationFilters($data, $q), ['type' => $type]);
            }

            $this->searchLogRepository->logSearch([
                'user_id' => $request->user()?->id,
                'session_id' => $this->resolveSessionId($request, $data['session_id'] ?? null),
                'query' => $q,
                'results_count' => $this->resolveResultsCount($paginator),
                'filters' => $this->normalizeFiltersForLog($logFilters),
                'created_at' => now(),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'query' => $q,
                    'type' => $type,
                    'results' => $paginator,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to search',
            ];
        }
    }

    /**
     * Build location filter array from validated request data.
     * (Xây dựng mảng filter cho địa điểm từ dữ liệu request đã xác thực)
     */
    private function mapLocationFilters(array $data, string $q): array
    {
        $filters = ['search' => $q];

        foreach (['category_id', 'subcategory_id', 'district', 'price_min', 'price_max', 'is_featured', 'sort_by', 'sort_order', 'page', 'per_page'] as $key) {
            if (isset($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        return $filters;
    }

    /**
     * Build tour filter array from validated request data.
     * (Xây dựng mảng filter cho tour từ dữ liệu request đã xác thực)
     */
    private function mapTourFilters(array $data, string $q): array
    {
        $filters = ['search' => $q];

        foreach (['tour_category_id', 'price_min', 'price_max', 'is_featured', 'is_hot', 'order_by', 'order_dir', 'page', 'per_page'] as $key) {
            if (isset($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        return $filters;
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

            $locationSuggestions = $this->locationRepository->getNameSuggestions($q, $limit);
            $tourSuggestions = $this->tourRepository->getNameSuggestions($q, $limit);

            $suggestions = collect($locationSuggestions)
                ->merge($tourSuggestions)
                ->unique()
                ->take($limit)
                ->values()
                ->all();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'query' => $q,
                    'suggestions' => $suggestions,
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get search suggestions',
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
                'message' => 'Failed to get popular searches',
            ];
        }
    }

    /**
     * Get trending search queries (last 24h).
     * (Lấy danh sách từ khóa tìm kiếm xu hướng - 24h qua)
     */
    public function trending(array $data): array
    {
        try {
            $limit = (int) ($data['limit'] ?? 10);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'trending' => $this->searchLogRepository->getPopularQueries($limit, 1),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get trending searches',
            ];
        }
    }

    /**
     * Get recommendations based on user history.
     * (Lấy gợi ý dựa trên lịch sử của người dùng)
     */
    public function recommendations(int $userId, array $data): array
    {
        try {
            $limit = (int) ($data['limit'] ?? 10);

            $viewedLocationIds = $this->viewRepository->getRecentLocationIds($userId, $limit);
            $favoritedLocationIds = $this->favoriteRepository->getRecentLocationIds($userId, $limit);
            $viewedTourIds = $this->viewRepository->getRecentTourIds($userId, $limit);
            $favoritedTourIds = $this->favoriteRepository->getRecentTourIds($userId, $limit);
            $bookedTourIds = $this->bookingRepository->getRecentTourIds($userId, $limit);

            $locationIds = collect($viewedLocationIds)->merge($favoritedLocationIds)->unique()->take($limit)->all();
            $tourIds = collect($viewedTourIds)->merge($favoritedTourIds)->merge($bookedTourIds)->unique()->take($limit)->all();

            $locations = $this->locationRepository->getByIds($locationIds);
            $tours = $this->tourRepository->getByIds($tourIds);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'locations' => $locations,
                    'tours' => $tours,
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get recommendations',
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
