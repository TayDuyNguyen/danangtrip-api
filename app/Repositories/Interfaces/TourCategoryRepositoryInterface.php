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
