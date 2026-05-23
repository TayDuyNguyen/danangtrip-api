<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        return $this->model->newQuery()
            ->withCount([
                'locations as locations_count' => function ($query): void {
                    $query->where('status', 'active');
                },
            ])
            ->with([
                'subcategories' => function ($query): void {
                    $query->where('status', 'active')->orderBy('sort_order');
                },
            ])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get a public category by id (active) with active subcategories.
     * (Lấy chi tiết danh mục public theo id kèm danh mục con đang hoạt động)
     */
    public function getPublicCategoryById(int $id): ?Category
    {
        return $this->model->newQuery()
            ->withCount([
                'locations as locations_count' => function ($query): void {
                    $query->where('status', 'active');
                },
            ])
            ->with([
                'subcategories' => function ($query): void {
                    $query->where('status', 'active')->orderBy('sort_order');
                },
            ])
            ->where('id', $id)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get categories with optional filters for admin.
     * (Lấy danh sách danh mục với bộ lọc tùy chọn cho admin)
     */
    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withCount([
                'locations as locations_count',
            ])
            ->with([
                'subcategories' => function ($query): void {
                    $query->orderBy('sort_order')->orderBy('id');
                },
            ]);

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $keyword = trim((string) $filters['search']);
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%');
            });
        }

        if (isset($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $query->where('status', $filters['status']);
        }

        $perPage = filter_var($filters['all'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? max((clone $query)->count(), 1)
            : (int) ($filters['per_page'] ?? Pagination::PER_PAGE->value);

        return $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * Get category detail for admin.
     * (Lấy chi tiết danh mục cho admin)
     */
    public function getAdminCategoryById(int $id): ?Category
    {
        return $this->model->newQuery()
            ->withCount([
                'locations as locations_count',
            ])
            ->with([
                'subcategories' => function ($query): void {
                    $query->orderBy('sort_order')->orderBy('id');
                },
            ])
            ->where('id', $id)
            ->first();
    }

    /**
     * Get aggregate stats for admin categories.
     * (Lấy thống kê tổng hợp cho danh mục admin)
     */
    public function getAdminStats(): array
    {
        $baseQuery = $this->model->newQuery();

        return [
            'total_categories' => (int) (clone $baseQuery)->count(),
            'active_categories' => (int) (clone $baseQuery)->where('status', 'active')->count(),
            'inactive_categories' => (int) (clone $baseQuery)->where('status', 'inactive')->count(),
            'total_locations' => (int) $this->model->newQuery()->withCount('locations')->get()->sum('locations_count'),
        ];
    }

    /**
     * Get paginated active locations belonging to a category slug.
     * (Lấy danh sách địa điểm đang hoạt động theo slug danh mục, có phân trang)
     */
    public function getLocationsBySlug(string $slug, array $filters = []): ?LengthAwarePaginator
    {
        $category = $this->model->newQuery()->where('slug', $slug)->where('status', 'active')->first();

        if (! ($category instanceof Category)) {
            return null;
        }

        $query = $category->locations()
            ->where('status', 'active')
            ->with(['category', 'subcategory', 'tags']);

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $searchTerm = $filters['search'];
            $driver = $this->model->getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $query->whereFullText(['name', 'address', 'description', 'short_description'], $searchTerm);
            } elseif ($driver === 'pgsql') {
                $query->whereRaw(
                    "to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(address, '') || ' ' || coalesce(description, '') || ' ' || coalesce(short_description, '')) @@ plainto_tsquery('simple', ?)",
                    [$searchTerm]
                );
            } else {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('address', 'like', "%{$searchTerm}%");
                });
            }
        }

        if (isset($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        if (isset($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (isset($filters['districts']) && is_array($filters['districts']) && count($filters['districts']) > 0) {
            $query->whereIn('district', $filters['districts']);
        }

        if (isset($filters['price_level'])) {
            $query->where('price_level', $filters['price_level']);
        }

        if (isset($filters['min_rating'])) {
            $query->where('avg_rating', '>=', $filters['min_rating']);
        }

        $validSortFields = ['created_at', 'avg_rating', 'review_count', 'view_count', 'price_min'];
        $sortBy = in_array($filters['sort_by'] ?? '', $validSortFields) ? $filters['sort_by'] : 'created_at';
        $sortOrder = in_array($filters['sort_order'] ?? '', ['asc', 'desc']) ? $filters['sort_order'] : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? Pagination::PER_PAGE->value);
        $page = (int) ($filters['page'] ?? Pagination::PAGE->value);

        return $query->paginate($perPage, ['*'], 'page', $page);
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
     * Reorder categories and normalize sequence.
     * (Sắp xếp lại danh mục và chuẩn hóa thứ tự)
     *
     * @param  array<int, array{id:int, sort_order:int}>  $items
     */
    public function reorder(array $items): bool
    {
        return DB::transaction(function () use ($items): bool {
            $requestedIds = collect($items)->pluck('id')->unique()->values()->all();
            $requestedCount = count($requestedIds);

            $existingIds = $this->model->newQuery()
                ->whereIn('id', $requestedIds)
                ->pluck('id')
                ->all();

            if ($requestedCount !== count($existingIds)) {
                return false;
            }

            $currentIds = $this->model->newQuery()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $orderedRequestedIds = collect($items)
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->pluck('id')
                ->unique()
                ->values()
                ->all();

            $remainingIds = array_values(array_diff($currentIds, $orderedRequestedIds));
            $finalOrderIds = array_merge($orderedRequestedIds, $remainingIds);

            $caseSql = collect($finalOrderIds)
                ->map(fn (int $id, int $index): string => 'WHEN '.$id.' THEN '.($index + 1))
                ->implode(' ');

            $this->model->newQuery()
                ->whereIn('id', $finalOrderIds)
                ->update([
                    'sort_order' => DB::raw('CASE id '.$caseSql.' END'),
                    'updated_at' => now(),
                ]);

            return true;
        });
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

    /**
     * Check if category has any locations.
     * (Kiểm tra xem danh mục có bất kỳ địa điểm nào không)
     */
    public function hasLocations(int $categoryId): bool
    {
        $category = $this->find($categoryId);

        return $category ? $category->locations()->exists() : false;
    }
}
