<?php

namespace App\Repositories\Interfaces;

use App\Models\Tour;
use App\Models\TourSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface TourRepositoryInterface
 * Define standard operations for Tour repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Tour)
 */
interface TourRepositoryInterface extends RepositoryInterface
{
    /**
     * Get tours with filters and pagination.
     * (Lấy danh sách tour với bộ lọc và phân trang)
     */
    public function getTours(array $filters = []): LengthAwarePaginator;

    /**
     * Get featured tours.
     * (Lấy tour nổi bật)
     */
    public function getFeaturedTours(?int $limit = null): Collection;

    /**
     * Get hot tours.
     * (Lấy tour hot)
     */
    public function getHotTours(?int $limit = null): Collection;

    /**
     * Find a tour by slug.
     * (Tìm tour theo slug)
     */
    public function findBySlug(string $slug): ?Tour;

    /**
     * Get schedules for a tour.
     * (Lấy lịch khởi hành của tour)
     */
    public function getSchedules(int $id): Collection;

    /**
     * Get ratings for a tour.
     * (Lấy đánh giá của tour)
     */
    public function getRatings(int $id, array $request): LengthAwarePaginator;

    /**
     * Get star rating statistics for a tour.
     * (Lấy thống kê số sao đánh giá của một tour)
     */
    public function getRatingStats(int $id): array;

    /**
     * Get a tour schedule for a specific date.
     * (Lấy lịch khởi hành của tour cho một ngày cụ thể)
     */
    public function getScheduleByDate(int $id, string $date): ?TourSchedule;

    /**
     * Update tour rating statistics.
     * (Cập nhật thống kê đánh giá của tour)
     */
    public function updateStats(int $id): bool;

    /**
     * Get tour data for export.
     * (Lấy dữ liệu tour để xuất)
     */
    public function getExportCollection(): Collection;
}
