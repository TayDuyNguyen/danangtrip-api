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
}
