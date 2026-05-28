<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getNextSortOrder(): int
    {
        return ((int) $this->model->newQuery()->max('sort_order')) + 1;
    }

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
}
