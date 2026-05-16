<?php

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface CategoryRepositoryInterface
 * Define standard operations for Category repository.
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get public categories (active) with active subcategories.
     * (Lấy danh mục public (đang hoạt động) kèm danh mục con đang hoạt động)
     */
    public function getPublicCategories(): Collection;

    /**
     * Get a public category by id (active) with active subcategories.
     * (Lấy chi tiết danh mục public theo id kèm danh mục con đang hoạt động)
     */
    public function getPublicCategoryById(int $id): ?Category;

    /**
     * Get categories with optional filters for admin.
     * (Lấy danh sách danh mục với bộ lọc tùy chọn cho admin)
     */
    public function getCategories(array $filters = []): LengthAwarePaginator;

    /**
     * Get category detail for admin.
     * (Lấy chi tiết danh mục cho admin)
     */
    public function getAdminCategoryById(int $id): ?Category;

    /**
     * Get aggregate stats for admin categories.
     * (Lấy thống kê tổng hợp cho danh mục admin)
     */
    public function getAdminStats(): array;

    /**
     * Get paginated active locations belonging to a category slug.
     * (Lấy danh sách địa điểm đang hoạt động theo slug danh mục, có phân trang)
     */
    public function getLocationsBySlug(string $slug, int $perPage): ?LengthAwarePaginator;

    /**
     * Update the status of a category.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Reorder categories and normalize sort order.
     * (Sắp xếp lại danh mục và chuẩn hóa thứ tự)
     *
     * @param  array<int, array{id:int, sort_order:int}>  $items
     */
    public function reorder(array $items): bool;

    /**
     * Check if category has any subcategories.
     * (Kiểm tra xem danh mục có bất kỳ danh mục con nào không)
     */
    public function hasSubcategories(int $categoryId): bool;

    /**
     * Check if category has any locations.
     * (Kiểm tra xem danh mục có bất kỳ địa điểm nào không)
     */
    public function hasLocations(int $categoryId): bool;
}
