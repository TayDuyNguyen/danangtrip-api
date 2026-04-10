<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return Category::class;
    }

    /**
     * Get public categories (active) with active subcategories.
     * (Lấy danh mục public (đang hoạt động) kèm danh mục con đang hoạt động)
     */
    public function getPublicCategories(): Collection
    {
        return $this->with([
            'subcategories' => function ($query): void {
                $query->where('status', 'active')->orderBy('sort_order');
            },
        ])
            ->orderBy('sort_order')
            ->getWhere(['status' => 'active']);
    }

    /**
     * Get a public category by id (active) with active subcategories.
     * (Lấy chi tiết danh mục public theo id kèm danh mục con đang hoạt động)
     */
    public function getPublicCategoryById(int $id): ?Category
    {
        return $this->with([
            'subcategories' => function ($query): void {
                $query->where('status', 'active')->orderBy('sort_order');
            },
        ])->firstWhere(['id' => $id, 'status' => 'active']);
    }

    /**
     * Get paginated active locations belonging to a category slug.
     * (Lấy danh sách địa điểm đang hoạt động theo slug danh mục, có phân trang)
     */
    public function getLocationsBySlug(string $slug, int $perPage): ?LengthAwarePaginator
    {
        $category = $this->model->newQuery()->where('slug', $slug)->where('status', 'active')->first();

        if (! ($category instanceof Category)) {
            return null;
        }

        return $category->locations()
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Update the status of a category.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->update($id, ['status' => $status]);
    }

    /**
     * Check if category has any subcategories.
     * (Kiểm tra xem danh mục có bất kỳ danh mục con nào không)
     */
    public function hasSubcategories(int $categoryId): bool
    {
        $category = $this->find($categoryId);

        return $category ? $category->subcategories()->exists() : false;
    }
}
