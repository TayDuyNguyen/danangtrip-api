<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface AmenityRepositoryInterface
 * (Giao diện Repository cho Tiện ích)
 */
interface AmenityRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all amenities with optional category filter.
     * (Lấy tất cả tiện ích với bộ lọc danh mục tùy chọn)
     */
    public function getAll(?string $category = null): Collection;
}
