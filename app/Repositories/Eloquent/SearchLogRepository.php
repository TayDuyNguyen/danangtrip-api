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

    private const SEARCH_ATTEMPT_COUNT_SQL = "
        COUNT(DISTINCT (
            COALESCE(NULLIF(session_id, ''), 'row:' || id::text)
            || ':' || LOWER(query)
            || ':' || to_char(date_trunc('minute', created_at), 'YYYYMMDDHH24MI')
        )) as count
    ";

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
            ->where('results_count', '>', 0)
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, '.self::SEARCH_ATTEMPT_COUNT_SQL)
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
            ->where('results_count', '>', 0)
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
        $query = $this->baseSearchEventsQuery($days)
            ->where('results_count', '>', 0);

        if (! empty($filters)) {
            $query->whereRaw('filters::jsonb @> ?::jsonb', [json_encode($filters)]);
        }

        return $query->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, '.self::SEARCH_ATTEMPT_COUNT_SQL)
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
            ->whereNotExists(function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('search_logs as positive_logs')
                    ->whereColumn('positive_logs.session_id', 'search_logs.session_id')
                    ->whereRaw('LOWER(positive_logs.query) = LOWER(search_logs.query)')
                    ->whereRaw("COALESCE(positive_logs.filters::jsonb ->> 'event', 'search') = 'search'")
                    ->where('positive_logs.results_count', '>', 0);
            })
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, '.self::SEARCH_ATTEMPT_COUNT_SQL)
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

        $displayExpression = "COALESCE(NULLIF(filters::jsonb ->> 'clicked_title', ''), query)";

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->whereRaw('LENGTH(TRIM(query)) >= 2')
            ->whereIn(DB::raw("COALESCE(filters::jsonb ->> 'event', 'search')"), $normalizedEvents)
            ->selectRaw("MIN($displayExpression) as query, LOWER($displayExpression) as normalized_query, COUNT(*) as count")
            ->groupByRaw("LOWER($displayExpression)")
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }

    public function getTopClickedItems(array $events, array $types, int $limit = 10, int $days = 30): array
    {
        $normalizedEvents = collect($events)
            ->map(fn ($event) => trim((string) $event))
            ->filter()
            ->values()
            ->all();

        $normalizedTypes = collect($types)
            ->map(fn ($type) => trim((string) $type))
            ->filter()
            ->values()
            ->all();

        if ($normalizedEvents === [] || $normalizedTypes === []) {
            return [];
        }

        $since = now()->subDays($days);
        $titleExpression = "filters::jsonb ->> 'clicked_title'";

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->whereIn(DB::raw("COALESCE(filters::jsonb ->> 'event', 'search')"), $normalizedEvents)
            ->whereIn(DB::raw("COALESCE(filters::jsonb ->> 'clicked_type', '')"), $normalizedTypes)
            ->whereRaw("LENGTH(TRIM(COALESCE($titleExpression, ''))) >= 2")
            ->selectRaw("
                MIN($titleExpression) as name,
                MIN(COALESCE(filters::jsonb ->> 'clicked_slug', '')) as slug,
                LOWER($titleExpression) as normalized_name,
                COALESCE(filters::jsonb ->> 'clicked_type', '') as type,
                COUNT(*) as count
            ")
            ->groupByRaw("LOWER($titleExpression), COALESCE(filters::jsonb ->> 'clicked_type', '')")
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'count' => (int) $row->count,
                'type' => (string) $row->type,
            ])
            ->all();
    }

    /**
     * Get trending search items with type info (tour/location).
     * Groups search events by query + type, returns top items with count and type.
     * (Lấy các mục xu hướng tìm kiếm kèm loại: tour hoặc địa điểm)
     *
     * @return array<int, array{query:string,count:int,type:string|null}>
     */
    public function getTrendingSearchItems(int $limit = 5, int $days = 7): array
    {
        $since = now()->subDays($days);

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->whereRaw("COALESCE(filters::jsonb ->> 'event', 'search') = 'search'")
            ->whereRaw('LENGTH(TRIM(query)) >= 2')
            ->where('results_count', '>', 0)
            ->selectRaw('MIN(query) as query, LOWER(query) as normalized_query, NULL as type, '.self::SEARCH_ATTEMPT_COUNT_SQL)
            ->groupByRaw('LOWER(query)')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'query' => (string) $row->query,
                'count' => (int) $row->count,
                'type' => null,
            ])
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
