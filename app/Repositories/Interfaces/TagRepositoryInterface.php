<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface TagRepositoryInterface
 * (Giao diện Repository cho Tag)
 */
interface TagRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all tags with optional type filter.
     * (Lấy tất cả tags với bộ lọc loại tùy chọn)
     */
    public function getAll(?string $type = null): Collection;
}
