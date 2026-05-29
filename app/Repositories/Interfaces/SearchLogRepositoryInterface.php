<?php

namespace App\Repositories\Interfaces;

/**
 * Interface SearchLogRepositoryInterface
 * Define standard operations for SearchLog repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Nhật ký tìm kiếm)
 */
interface SearchLogRepositoryInterface extends RepositoryInterface
{
    /**
     * Store a search log entry.
     * (Lưu một bản ghi nhật ký tìm kiếm)
     *
     * @param  array  $data  Search log data.
     * @return void No return value.
     */
    public function logSearch(array $data): void;

    /**
     * Get popular search queries.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến)
     *
     * @param  int  $limit  Max items to return.
     * @param  int  $days  Lookback window (days).
     * @return array<int, array{query:string,count:int}> Popular queries and counts.
     */
    public function getPopularQueries(int $limit = 10, int $days = 30): array;

    /**
     * Get query suggestions by prefix.
     * (Lấy gợi ý từ khóa tìm kiếm theo tiền tố)
     *
     * @param  string  $q  Query prefix.
     * @param  int  $limit  Max items to return.
     * @param  int  $days  Lookback window (days).
     * @return string[] Suggested queries.
     */
    public function getQuerySuggestions(string $q, int $limit = 5, int $days = 30): array;

    /**
     * Get popular search queries with optional filters.
     * (Lấy danh sách từ khóa tìm kiếm phổ biến với bộ lọc tùy chọn)
     *
     * @param  array  $filters  Filters to apply (e.g., ['district' => 'Hai Chau']).
     * @param  int  $limit  Max items to return.
     * @param  int  $days  Lookback window (days).
     * @return array<int, array{query:string,count:int}> Popular queries and counts.
     */
    public function getPopularQueriesByFilters(array $filters = [], int $limit = 10, int $days = 30): array;

    /**
     * Get queries that returned no results.
     * (Lấy các từ khóa tìm kiếm không có kết quả)
     *
     * @return array<int, array{query:string,count:int}>
     */
    public function getZeroResultQueries(int $limit = 10, int $days = 30): array;

    /**
     * Get top queries by interaction events such as suggestion/result clicks.
     * (Lấy các từ khóa có nhiều tương tác như click suggestion/kết quả)
     *
     * @param  string[]  $events
     * @return array<int, array{query:string,count:int}>
     */
    public function getTopInteractionQueries(array $events, int $limit = 10, int $days = 30): array;
}
