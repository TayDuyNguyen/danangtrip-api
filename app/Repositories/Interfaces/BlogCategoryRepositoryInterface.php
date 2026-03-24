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
}
