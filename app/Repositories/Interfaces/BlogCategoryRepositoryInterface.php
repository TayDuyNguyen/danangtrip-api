<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface BlogCategoryRepositoryInterface
 * (Giao diện Repository cho danh mục Blog)
 */
interface BlogCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all blog categories.
     * (Lấy tất cả danh mục Blog)
     */
    public function getAllCategories(): Collection;

    /**
     * Get next sort order for a new category.
     * (Lấy thứ tự sắp xếp kế tiếp cho danh mục mới)
     */
    public function getNextSortOrder(): int;

    /**
     * Reorder blog categories and normalize sequence.
     * (Sắp xếp lại danh mục bài viết và chuẩn hóa thứ tự)
     *
     * @param  array<int, array{id:int, sort_order:int}>  $items
     */
    public function reorder(array $items): bool;
}
