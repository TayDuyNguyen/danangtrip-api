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
use Illuminate\Support\Facades\Log;

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
            $q = $this->normalizeQuery((string) ($data['q'] ?? ''));
            $type = $data['type'] ?? 'location';

            if ($type === 'tour') {
                $paginator = $this->tourRepository->getTours($this->mapTourFilters($data, $q));
                $logFilters = array_merge($this->mapTourFilters($data, $q), ['type' => $type]);
            } else {
                $paginator = $this->locationRepository->getLocations($this->mapLocationFilters($data, $q));
                $logFilters = array_merge($this->mapLocationFilters($data, $q), ['type' => $type]);
            }

            if ($this->shouldLogSearch($q)) {
                try {
                    $this->searchLogRepository->logSearch([
                        'user_id' => $request->user()?->id,
                        'session_id' => $this->resolveSessionId($request, $data['session_id'] ?? null),
                        'query' => $q,
                        'results_count' => $this->resolveResultsCount($paginator),
                        'filters' => $this->normalizeFiltersForLog($logFilters),
                        'created_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Search logging failed', [
                        'query' => $q,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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

        foreach (['category_id', 'subcategory_id', 'district', 'price_min', 'price_max', 'min_rating', 'is_featured', 'sort_by', 'sort_order', 'page', 'per_page'] as $key) {
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

        foreach (['tour_category_id', 'price_min', 'price_max', 'min_rating', 'is_featured', 'is_hot', 'sort_by', 'sort_order', 'order_by', 'order_dir', 'page', 'per_page'] as $key) {
            if (isset($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        // Backward compatibility for old clients sending order_* parameters.
        if (! isset($filters['sort_by']) && isset($filters['order_by'])) {
            $filters['sort_by'] = $filters['order_by'];
        }
        if (! isset($filters['sort_order']) && isset($filters['order_dir'])) {
            $filters['sort_order'] = $filters['order_dir'];
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
            $type = (string) ($data['type'] ?? 'all');
            $locationFilters = $this->mapLocationSuggestionFilters($data);
            $tourFilters = $this->mapTourSuggestionFilters($data);
            $hasScopedFilters = count($locationFilters) > 0 || count($tourFilters) > 0;
            $intentSuggestions = $type === 'all' && ! $hasScopedFilters
                ? $this->buildIntentSuggestions($q, $limit)
                : [];

            $locationSuggestions = $type === 'tour'
                ? []
                : $this->locationRepository->getNameSuggestions($q, $limit, $locationFilters);
            $tourSuggestions = $type === 'location'
                ? []
                : $this->tourRepository->getNameSuggestions($q, $limit, $tourFilters);

            $suggestions = collect($intentSuggestions)
                ->concat($locationSuggestions)
                ->merge($tourSuggestions)
                ->unique(function ($item) {
                    if (! is_array($item)) {
                        return mb_strtolower((string) $item);
                    }

                    $type = (string) ($item['type'] ?? 'keyword');
                    $label = (string) ($item['title'] ?? $item['name'] ?? '');

                    return $type.':'.mb_strtolower($label);
                })
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
     * Build location filters for suggestion queries.
     * (Xây dựng bộ lọc địa điểm cho autocomplete)
     */
    private function mapLocationSuggestionFilters(array $data): array
    {
        $filters = [];

        foreach (['category_id', 'district', 'price_min', 'price_max', 'min_rating'] as $key) {
            if (isset($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        return $filters;
    }

    /**
     * Build tour filters for suggestion queries.
     * (Xây dựng bộ lọc tour cho autocomplete)
     */
    private function mapTourSuggestionFilters(array $data): array
    {
        $filters = [];

        foreach (['tour_category_id', 'price_min', 'price_max', 'min_rating'] as $key) {
            if (isset($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        return $filters;
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
     * Get blended trend insights for the search UI.
     * (Lấy insight xu hướng kết hợp cho giao diện tìm kiếm)
     */
    public function trendInsights(array $data): array
    {
        try {
            $limit = (int) ($data['limit'] ?? 10);
            $keywordLimit = max(1, min($limit, 10));
            $locationLimit = max(1, min($limit, 10));

            $trendingKeywords = $this->searchLogRepository->getPopularQueries($keywordLimit, 1);
            $popularKeywords = $this->searchLogRepository->getPopularQueries($keywordLimit, 30);
            $topLocations = $this->locationRepository->getTopLocations($locationLimit);

            $locationItems = $topLocations->map(function ($location) {
                return [
                    'query' => (string) $location->name,
                    'count' => (int) ($location->view_count ?? 0),
                    'source' => 'location',
                    'slug' => (string) ($location->slug ?? ''),
                    'district' => (string) ($location->district ?? ''),
                ];
            })->values();

            $keywordItems = collect($trendingKeywords)->map(function (array $item) {
                return [
                    'query' => (string) $item['query'],
                    'count' => (int) $item['count'],
                    'source' => 'keyword',
                ];
            });

            $fallbackKeywordItems = collect($popularKeywords)->map(function (array $item) {
                return [
                    'query' => (string) $item['query'],
                    'count' => (int) $item['count'],
                    'source' => 'keyword',
                ];
            });

            $topBookedTours = collect($this->bookingRepository->getTopTours($limit, null, null))
                ->map(function (array $tour) {
                    return [
                        'query' => (string) ($tour['name'] ?? ''),
                        'count' => (int) ($tour['booking_count'] ?? 0),
                        'source' => 'tour',
                        'slug' => (string) ($tour['slug'] ?? ''),
                    ];
                });

            $topClickedLocations = collect($this->searchLogRepository->getTopClickedItems(
                ['suggestion_click', 'trending_click', 'result_click'],
                ['location'],
                $limit,
                30
            ))->map(function (array $location) {
                return [
                    'query' => (string) ($location['name'] ?? ''),
                    'count' => (int) ($location['count'] ?? 0),
                    'source' => 'location',
                    'slug' => (string) ($location['slug'] ?? ''),
                ];
            });

            $fallbackTrendItems = $keywordItems
                ->concat($fallbackKeywordItems);

            $trendItems = collect();
            for ($index = 0; $trendItems->count() < $limit && $index < max($topBookedTours->count(), $topClickedLocations->count()); $index++) {
                if ($topBookedTours->has($index)) {
                    $trendItems->push($topBookedTours->get($index));
                }
                if ($topClickedLocations->has($index)) {
                    $trendItems->push($topClickedLocations->get($index));
                }
            }

            if ($trendItems->isEmpty()) {
                $trendItems = $fallbackTrendItems;
            }

            $trendItems = $trendItems
                ->filter(fn (array $item) => trim((string) ($item['query'] ?? '')) !== '' && (int) ($item['count'] ?? 0) > 0)
                ->unique(fn (array $item) => ((string) ($item['source'] ?? 'keyword')).':'.mb_strtolower(trim((string) $item['query'])))
                ->take($limit)
                ->values()
                ->all();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'items' => $trendItems,
                    'trending_keywords' => $trendingKeywords,
                    'popular_keywords' => $popularKeywords,
                    'top_locations' => $locationItems->all(),
                ],
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get search trend insights',
            ];
        }
    }

    /**
     * Track non-search interactions from the search UI.
     * (Ghi nhận các tương tác ngoài hành động tìm kiếm trực tiếp trên giao diện search)
     */
    public function trackInteraction(array $data, Request $request): array
    {
        try {
            $query = $this->normalizeQuery((string) ($data['query'] ?? ''));
            if (! $this->shouldLogSearch($query)) {
                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => ['logged' => false],
                ];
            }

            $filters = array_filter([
                'event' => $data['event'] ?? null,
                'type' => $data['type'] ?? null,
                'clicked_title' => $this->normalizeQuery((string) ($data['clicked_title'] ?? '')),
                'clicked_slug' => (string) ($data['clicked_slug'] ?? ''),
                'clicked_type' => $data['clicked_type'] ?? null,
                'source' => $data['source'] ?? null,
                'page' => $data['page'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            $this->searchLogRepository->logSearch([
                'user_id' => $request->user()?->id,
                'session_id' => $this->resolveSessionId($request, $data['session_id'] ?? null),
                'query' => $query,
                'results_count' => 0,
                'filters' => $filters,
                'created_at' => now(),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['logged' => true],
            ];
        } catch (\Throwable $e) {
            Log::warning('Search interaction logging failed', [
                'event' => $data['event'] ?? null,
                'query' => $data['query'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['logged' => false],
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
            foreach ($locations as $location) {
                $reason = 'popular';
                if (in_array($location->id, $favoritedLocationIds)) {
                    $reason = 'similar_favorite';
                } elseif (in_array($location->id, $viewedLocationIds)) {
                    $reason = 'viewed';
                }
                $location->setAttribute('recommendation_reason', $reason);
            }

            $tours = $this->tourRepository->getByIds($tourIds);
            foreach ($tours as $tour) {
                $reason = 'popular';
                if (in_array($tour->id, $bookedTourIds)) {
                    $reason = 'booked';
                } elseif (in_array($tour->id, $favoritedTourIds)) {
                    $reason = 'similar_favorite';
                } elseif (in_array($tour->id, $viewedTourIds)) {
                    $reason = 'viewed';
                }
                $tour->setAttribute('recommendation_reason', $reason);
            }

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

    /**
     * Normalize raw query string before search logging.
     * (Chuẩn hóa từ khóa trước khi ghi log tìm kiếm)
     */
    private function normalizeQuery(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return is_string($normalized) ? $normalized : trim($value);
    }

    /**
     * Decide whether the query is meaningful enough to record.
     * (Xác định từ khóa có đủ ý nghĩa để ghi nhận hay không)
     */
    private function shouldLogSearch(string $query): bool
    {
        return $query !== '' && mb_strlen($query) >= 2;
    }

    /**
     * Build lightweight intent suggestions for travel search.
     * (Tạo gợi ý theo nhu cầu/ngữ cảnh cho ô tìm kiếm du lịch)
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildIntentSuggestions(string $query, int $limit): array
    {
        $normalized = mb_strtolower($this->normalizeQuery($query));
        if ($normalized === '') {
            return [];
        }

        $catalog = [
            [
                'tokens' => ['bien', 'biển', 'gan bien', 'gần biển', 'beach', 'resort'],
                'items' => [
                    ['title' => 'Bãi biển đẹp ở Đà Nẵng', 'subtitle' => 'Gợi ý các điểm tắm biển nổi bật như Mỹ Khê và Non Nước'],
                    ['title' => 'Resort gần biển Mỹ Khê', 'subtitle' => 'Khám phá nơi nghỉ dưỡng gần biển cho kỳ nghỉ thư giãn'],
                ],
            ],
            [
                'tokens' => ['an toi', 'ăn tối', 'hai san', 'hải sản', 'food', 'am thuc', 'ẩm thực'],
                'items' => [
                    ['title' => 'Ăn tối ở Đà Nẵng', 'subtitle' => 'Nhà hàng hải sản, quán ăn gia đình và địa điểm mở cửa buổi tối'],
                    ['title' => 'Hải sản gần biển', 'subtitle' => 'Tìm quán hải sản nổi tiếng gần Mỹ Khê và Sơn Trà'],
                ],
            ],
            [
                'tokens' => ['gia dinh', 'gia đình', 'tre em', 'trẻ em', 'family'],
                'items' => [
                    ['title' => 'Điểm đến cho gia đình', 'subtitle' => 'Gợi ý tour nhẹ nhàng và địa điểm phù hợp cho trẻ em'],
                    ['title' => 'Tour gia đình 1 ngày', 'subtitle' => 'Lịch trình ngắn phù hợp cho nhóm gia đình và người lớn tuổi'],
                ],
            ],
            [
                'tokens' => ['dem', 'đêm', 'toi', 'tối', 'night'],
                'items' => [
                    ['title' => 'Đà Nẵng về đêm', 'subtitle' => 'Cầu Rồng, chợ đêm, rooftop và điểm vui chơi buổi tối'],
                    ['title' => 'Quán ăn mở cửa buổi tối', 'subtitle' => 'Gợi ý ăn đêm, cafe và địa điểm ngắm sông Hàn'],
                ],
            ],
            [
                'tokens' => ['1 ngay', '1 ngày', 'nua ngay', 'nửa ngày', 'short trip'],
                'items' => [
                    ['title' => 'Lịch trình Đà Nẵng 1 ngày', 'subtitle' => 'Gợi ý lịch trình ngắn gọn cho du khách ở lại ít ngày'],
                    ['title' => 'Tour nửa ngày nổi bật', 'subtitle' => 'Chọn nhanh các tour ngắn phù hợp buổi sáng hoặc chiều'],
                ],
            ],
        ];

        $matched = collect($catalog)
            ->first(function (array $entry) use ($normalized) {
                foreach ($entry['tokens'] as $token) {
                    if (str_contains($normalized, (string) $token)) {
                        return true;
                    }
                }

                return false;
            });

        if (! is_array($matched)) {
            return [];
        }

        return collect($matched['items'])
            ->take(max(1, min($limit, 3)))
            ->values()
            ->map(function (array $item, int $index) {
                return [
                    'id' => -1000 - $index,
                    'type' => 'keyword',
                    'title' => (string) $item['title'],
                    'slug' => '',
                    'subtitle' => (string) $item['subtitle'],
                    'thumbnail' => null,
                    'score' => 90 - ($index * 5),
                ];
            })
            ->all();
    }
}
