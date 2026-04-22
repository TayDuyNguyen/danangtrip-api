<?php

namespace App\Repositories\Interfaces;

use App\Models\TourCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface TourCategoryRepositoryInterface
 * Define standard operations for TourCategory repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Danh mục tour)
 */
interface TourCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get active tour categories.
     * (Lấy danh sách danh mục tour đang hoạt động)
     */
    public function getActiveCategories(): Collection;

    /**
     * Get paginated tours by category slug.
     * (Lấy danh sách tour theo slug danh mục, có phân trang)
     */
    public function getToursBySlug(string $slug, array $filters = []): ?LengthAwarePaginator;

    /**
     * Get categories with optional filters (Admin).
     * (Lấy danh sách danh mục với các bộ lọc tùy chọn - Admin)
     */
    public function getCategories(array $filters = []): LengthAwarePaginator;

    /**
     * Get aggregate stats for admin list.
     * (Lấy thống kê tổng hợp cho danh sách admin)
     */
    public function getAdminStats(): array;

    /**
     * Get next available sort order.
     * (Lấy thứ tự kế tiếp khả dụng)
     */
    public function getNextSortOrder(): int;

    /**
     * Reorder categories and normalize sequence.
     * (Sắp xếp lại danh mục và chuẩn hóa thứ tự)
     *
     * @param  array<int, array{id:int, sort_order:int}>  $items
     */
    public function reorder(array $items): bool;

    /**
     * Update category status.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Check if category has any tours.
     * (Kiểm tra xem danh mục có bất kỳ tour nào không)
     */
    public function hasTours(int $id): bool;
}
