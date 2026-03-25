<?php

namespace App\Repositories\Eloquent;

use App\Models\Amenity;
use App\Repositories\Interfaces\AmenityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class AmenityRepository
 * (Lớp triển khai Repository cho Tiện ích bằng Eloquent)
 */
final class AmenityRepository extends BaseRepository implements AmenityRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Amenity::class;
    }

    /**
     * Get all amenities with optional category filter.
     * (Lấy tất cả tiện ích với bộ lọc danh mục tùy chọn)
     */
    public function getAll(?string $category = null): Collection
    {
        $query = $this->model->newQuery();

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('name')->get();
    }
}
