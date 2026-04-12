<?php

namespace App\Repositories\Eloquent;

use App\Models\Tag;
use App\Repositories\Interfaces\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TagRepository
 * (Lớp triển khai Repository cho Tag bằng Eloquent)
 */
final class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Tag::class;
    }

    /**
     * Get all tags with optional type filter.
     * (Lấy tất cả tags với bộ lọc loại tùy chọn)
     */
    public function getAll(?string $type = null): Collection
    {
        $query = $this->model->newQuery();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('name')->get();
    }
}
