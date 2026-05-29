<?php

namespace App\Repositories\Eloquent;

use App\Models\SearchLog;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class SearchLogRepository
 * Eloquent implementation of SearchLogRepositoryInterface.
 * (Thực thi Eloquent cho SearchLogRepositoryInterface)
 */
final class SearchLogRepository extends BaseRepository implements SearchLogRepositoryInterface
{
    private const DEDUPE_MINUTES = 30;

    private function baseSearchEventsQuery(int $days)
    {
        $since = now()->subDays($days);

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->whereRaw("COALESCE(filters::jsonb ->> 'event', 'search') = 'search'")
            ->whereRaw('LENGTH(TRIM(query)) >= 2');
    }

    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string The model class name.
     */
    public function getModel(): string
    {
        return SearchLog::class;
    }

    /**
     * Store a search log entry.
     * (Lưu một bản ghi nhật ký tìm kiếm)
     *
     * @param  array  $data  Search log data.
     * @return void No return value.
     */
    public function logSearch(array $data): void
    {
        $query = $this->normalizeQuery((string) ($data['query'] ?? ''));
        if ($query === '') {
            return;
        }

        $sessionId = (string) ($data['session_id'] ?? '');
        $type = data_get($data, 'filters.type');
        $event = (string) (data_get($data, 'filters.event') ?? 'search');
        $clickedSlug = (string) (data_get($data, 'filters.clicked_slug') ?? '');

        $exists = $this->model->newQuery()
            ->where('session_id', $sessionId)
            ->whereRaw('LOWER(query) = ?', [mb_strtolower($query)])
            ->whereRaw("COALESCE(filters->>'event', 'search') = ?", [$event])
            ->when($type, function ($builder, $searchType) {
                $builder->whereRaw("COALESCE(filters->>'type', '') = ?", [(string) $searchType]);
            })
            ->when($clickedSlug !== '', function ($builder) use ($clickedSlug) {
                $builder->whereRaw("COALESCE(filters->>'clicked_slug', '') = ?", [$clickedSlug]);
            })
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->exists();

        if ($exists) {
            return;
        }

        $this->model->create([
            ...$data,
            'query' => $query,
        ]);
    }

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     */
    public function getPopularQueries(int $limit = 10, int $days = 30): array
    {
        return $this->baseSearchEventsQuery($days)
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, COUNT(*) as count')
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * Get query suggestions by prefix.
     * (Lấy gợi ý từ khóa tìm kiếm theo tiền tố)
     *
     * @param  string  $q  Query prefix.
     * @param  int  $limit  Max items to return.
     * @param  int  $days  Lookback window (days).
     * @return string[] Suggested queries.
     */
    public function getQuerySuggestions(string $q, int $limit = 5, int $days = 30): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        return $this->baseSearchEventsQuery($days)
            ->where('query', 'ilike', '%'.$q.'%')
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, COUNT(*) as count')
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('query')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    /**
     * Get popular search queries with optional filters.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến với bộ lọc tùy chọn)
     */
    public function getPopularQueriesByFilters(array $filters = [], int $limit = 10, int $days = 30): array
    {
        $query = $this->baseSearchEventsQuery($days);

        if (! empty($filters)) {
            $query->whereRaw('filters::jsonb @> ?::jsonb', [json_encode($filters)]);
        }

        return $query->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, COUNT(*) as count')
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }

    public function getZeroResultQueries(int $limit = 10, int $days = 30): array
    {
        return $this->baseSearchEventsQuery($days)
            ->where('results_count', 0)
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, COUNT(*) as count')
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }

    public function getTopInteractionQueries(array $events, int $limit = 10, int $days = 30): array
    {
        $normalizedEvents = collect($events)
            ->map(fn ($event) => trim((string) $event))
            ->filter()
            ->values()
            ->all();

        if ($normalizedEvents === []) {
            return [];
        }

        $since = now()->subDays($days);

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->whereRaw('LENGTH(TRIM(query)) >= 2')
            ->whereIn(DB::raw("COALESCE(filters::jsonb ->> 'event', 'search')"), $normalizedEvents)
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, COUNT(*) as count')
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * Normalize stored search query.
     * (Chuẩn hóa từ khóa tìm kiếm trước khi lưu)
     */
    private function normalizeQuery(string $query): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($query));

        return is_string($normalized) ? $normalized : trim($query);
    }
}
