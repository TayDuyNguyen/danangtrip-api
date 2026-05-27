<?php

namespace App\Repositories\Eloquent;

use App\Models\TourCategory;
use App\Repositories\Interfaces\TourCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Class TourCategoryRepository
 * Eloquent implementation of TourCategoryRepositoryInterface.
 * (Triển khai Eloquent cho TourCategoryRepositoryInterface)
 */
class TourCategoryRepository extends BaseRepository implements TourCategoryRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return TourCategory::class;
    }

    /**
     * Get active tour categories.
     * (Lấy danh sách danh mục tour đang hoạt động)
     */
    public function getActiveCategories(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get paginated tours by category slug.
     * (Lấy danh sách tour theo slug danh mục, có phân trang)
     */
    public function getToursBySlug(string $slug, array $filters = []): ?LengthAwarePaginator
    {
        $category = $this->model->newQuery()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (! $category) {
            return null;
        }

        $perPage = $filters['per_page'] ?? 10;

        $allowedSorts = ['id', 'name', 'price_adult', 'created_at', 'rating_avg'];
        $sort = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'created_at';
        $order = strtolower($filters['sort_order'] ?? '') === 'asc' ? 'asc' : 'desc';

        $tourQuery = $category->tours()
            ->where('status', 'active');

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $driver = $this->model->getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $tourQuery->whereFullText(['name', 'description', 'itinerary', 'inclusions', 'exclusions'], $searchTerm);
            } elseif ($driver === 'pgsql') {
                $tourQuery->whereRaw(
                    "to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(itinerary::text, '') || ' ' || coalesce(inclusions::text, '') || ' ' || coalesce(exclusions::text, '')) @@ plainto_tsquery('simple', ?)",
                    [$searchTerm]
                );
            } else {
                $tourQuery->where('name', 'like', '%'.$searchTerm.'%');
            }
        }

        if (isset($filters['price_min'])) {
            $tourQuery->where('price_adult', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $tourQuery->where('price_adult', '<=', $filters['price_max']);
        }

        if (isset($filters['duration'])) {
            $tourQuery->where('duration', 'like', '%'.$filters['duration'].'%');
        }

        if (isset($filters['available_from']) || isset($filters['available_to'])) {
            $tourQuery->whereHas('schedules', function ($q) use ($filters) {
                $today = now()->toDateString();

                if (isset($filters['available_from'])) {
                    $startDateLimit = $filters['available_from'] < $today ? $today : $filters['available_from'];
                    $q->where('start_date', '>=', $startDateLimit);
                } else {
                    $q->where('start_date', '>=', $today);
                }

                if (isset($filters['available_to'])) {
                    $q->where('start_date', '<=', $filters['available_to']);
                }

                $q->where('status', 'available')
                    ->where('booking_availability', 'open');
            });
        }

        if (isset($filters['booking_availability'])) {
            $tourQuery->where('booking_availability', $filters['booking_availability']);
        }

        return $tourQuery->orderBy($sort, $order)->paginate($perPage);
    }

    /**
     * Get categories with optional filters (Admin).
     * (Lấy danh sách danh mục với các bộ lọc tùy chọn - Admin)
     */
    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withCount(['tours as tour_count']);

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $keyword = trim((string) $filters['search']);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%');
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = filter_var($filters['all'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ? max((clone $query)->count(), 1)
            : ($filters['per_page'] ?? 10);

        return $query->orderBy('sort_order')->paginate($perPage);
    }

    /**
     * Get aggregate stats for admin list.
     * (Lấy thống kê tổng hợp cho danh sách admin)
     */
    public function getAdminStats(): array
    {
        $baseQuery = $this->model->newQuery();

        return [
            'total_categories' => (int) (clone $baseQuery)->count(),
            'active_categories' => (int) (clone $baseQuery)->where('status', 'active')->count(),
            'inactive_categories' => (int) (clone $baseQuery)->where('status', 'inactive')->count(),
            'total_tours' => (int) $this->model->newQuery()->withCount('tours')->get()->sum('tours_count'),
        ];
    }

    /**
     * Get next available sort order.
     * (Lấy thứ tự kế tiếp khả dụng)
     */
    public function getNextSortOrder(): int
    {
        return ((int) $this->model->newQuery()->max('sort_order')) + 1;
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
     * Update category status.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(int $id, string $status): bool
    {
        $category = $this->find($id);
        if (! $category) {
            return false;
        }

        return $category->update(['status' => $status]);
    }

    /**
     * Check if category has any tours.
     * (Kiểm tra xem danh mục có bất kỳ tour nào không)
     */
    public function hasTours(int $id): bool
    {
        $category = $this->find($id);
        if (! $category) {
            return false;
        }

        return $category->tours()->exists();
    }
}
