<?php

namespace App\Repositories\Interfaces;

use App\Models\Rating;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface RatingRepositoryInterface
 * Define standard operations for Rating repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Đánh giá)
 */
interface RatingRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginate ratings for admin moderation.
     * (Phân trang danh sách đánh giá cho admin)
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator;

    /**
     * Find rating for update (row lock) with relations.
     * (Tìm đánh giá để cập nhật (khóa dòng) kèm quan hệ)
     */
    public function findForUpdate(int $id, array $relations = []): ?Rating;

    /**
     * Increment helpful_count if rating is approved.
     * (Tăng helpful_count nếu đánh giá đã được duyệt)
     */
    public function incrementHelpfulIfApproved(int $id): int;

    /**
     * Get approved rating stats for a location.
     * (Lấy thống kê đánh giá đã duyệt cho một địa điểm)
     *
     * @return array{review_count:int,avg_rating:float}
     */
    public function getApprovedStatsForLocation(int $locationId): array;

    /**
     * Get approved rating stats for a tour.
     * (Lấy thống kê đánh giá đã duyệt cho một tour)
     *
     * @return array{review_count:int,avg_rating:float}
     */
    public function getApprovedStatsForTour(int $tourId): array;

    /**
     * Get ratings by user with filters and pagination.
     * (Lấy danh sách đánh giá của người dùng với bộ lọc và phân trang)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator;

    /**
     * Check if a user has already rated an item.
     * (Kiểm tra xem người dùng đã đánh giá một mục chưa)
     */
    public function checkUserRated(int $userId, array $params): ?Rating;

    /**
     * Collection of ratings for export.
     * (Duyệt danh sách đánh giá phục vụ export)
     */
    public function searchForExport(array $filters): Collection;

    /**
     * Get rating stats grouped by date and status.
     * (Lấy thống kê đánh giá theo ngày và trạng thái)
     */
    public function getStatsByDateAndStatus(?string $fromDate = null, ?string $toDate = null, ?string $status = null): array;
}
