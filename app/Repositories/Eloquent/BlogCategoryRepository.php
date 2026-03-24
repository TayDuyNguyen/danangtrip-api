<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class BlogCategoryRepository
 * (Lớp triển khai Repository cho danh mục Blog bằng Eloquent)
 */
final class BlogCategoryRepository extends BaseRepository implements BlogCategoryRepositoryInterface
{
    /**
     * Specify model class name.
     * (Chỉ định tên lớp Model)
     */
    public function getModel(): string
    {
        return BlogCategory::class;
    }

    /**
     * Get all blog categories.
     * (Lấy tất cả danh mục Blog)
     */
    public function getAllCategories(): Collection
    {
        return $this->model->newQuery()
            ->withCount('posts')
            ->orderBy('name')
            ->get();
    }
}
