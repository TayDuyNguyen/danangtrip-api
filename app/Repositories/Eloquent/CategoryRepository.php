<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function getModel()
    {
        return Category::class;
    }

    /**
     *  Get public categories (active) with active subcategories
     *  (Lấy danh mục public (đang hoạt động) kèm danh mục con đang hoạt động)
     */
    public function getPublicCategories(): Collection
    {
        $this->clearQuery();
        $this->with([
            'subcategories' => function ($query): void {
                $query->where('status', 'active')->orderBy('sort_order');
            },
        ]);

        $query = $this->getQuery();
        $categories = $query
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        return $categories;
    }

    /**
     * Get a public category by id (active) with active subcategories.
     * (Lấy chi tiết danh mục public theo id kèm danh mục con đang hoạt động)
     */
    public function getPublicCategoryById(int $id): ?Category
    {
        $this->clearQuery();
        $this->with([
            'subcategories' => function ($query): void {
                $query->where('status', 'active')->orderBy('sort_order');
            },
        ]);

        $query = $this->getQuery();
        $category = $query
            ->where('id', $id)
            ->where('status', 'active')
            ->first();

        return $category;
    }
}
