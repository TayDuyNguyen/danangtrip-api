<?php

namespace App\Repositories\Eloquent;

use App\Models\SearchLog;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;

/**
 * Class SearchLogRepository
 * Eloquent implementation of SearchLogRepositoryInterface.
 * (Thực thi Eloquent cho SearchLogRepositoryInterface)
 */
final class SearchLogRepository extends BaseRepository implements SearchLogRepositoryInterface
{
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
        $this->model->create($data);
    }

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     */
    public function getPopularQueries(int $limit = 10, int $days = 30): array
    {
        $since = now()->subDays($days);

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
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

        $since = now()->subDays($days);

        return $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since)
            ->where('query', 'ilike', '%'.$q.'%')
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
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
        $since = now()->subDays($days);

        $query = $this->model->newQuery()
            ->whereNotNull('created_at')
            ->where('created_at', '>=', $since);

        if (! empty($filters)) {
            $query->whereRaw('filters::jsonb @> ?::jsonb', [json_encode($filters)]);
        }

        return $query->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['query' => (string) $row->query, 'count' => (int) $row->count])
            ->all();
    }
}
