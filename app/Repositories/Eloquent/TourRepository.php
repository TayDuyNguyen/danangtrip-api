<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Repositories\Interfaces\TourRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class TourRepository
 * Eloquent implementation of TourRepositoryInterface.
 * (Triển khai Eloquent cho TourRepositoryInterface)
 */
class TourRepository extends BaseRepository implements TourRepositoryInterface
{
    /**
     * Get the model class name.
     * (Lấy tên lớp Model)
     *
     * @return string
     */
    public function getModel()
    {
        return Tour::class;
    }

    /**
     * Get tours with filters and pagination.
     * (Lấy danh sách tour với bộ lọc và phân trang)
     */
    public function getTours(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (isset($filters['tour_category_id'])) {
            $query->where('tour_category_id', $filters['tour_category_id']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price_adult', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price_adult', '<=', $filters['price_max']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (isset($filters['is_hot'])) {
            $query->where('is_hot', (bool) $filters['is_hot']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'available');
        }

        $validSortFields = ['created_at', 'price_adult', 'view_count', 'name', 'rating_avg'];
        $orderBy = in_array($filters['sort_by'] ?? '', $validSortFields) ? $filters['sort_by'] : 'created_at';
        $orderDir = in_array($filters['sort_order'] ?? '', ['asc', 'desc']) ? $filters['sort_order'] : 'desc';
        $query->orderBy($orderBy, $orderDir);

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;

        return $query->paginate($perPage);
    }

    /**
     * Get featured tours.
     * (Lấy tour nổi bật)
     */
    public function getFeaturedTours(?int $limit = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('status', 'available')
            ->where('is_featured', true);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get hot tours.
     * (Lấy tour hot)
     */
    public function getHotTours(?int $limit = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('status', 'available')
            ->where('is_hot', true);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Find a tour by slug.
     * (Tìm tour theo slug)
     */
    public function findBySlug(string $slug): ?Tour
    {
        return $this->model->newQuery()
            ->with(['category', 'schedules' => function ($query) {
                $query->where('start_date', '>=', now())->orderBy('start_date', 'asc');
            }])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get schedules for a tour.
     * (Lấy lịch khởi hành của tour)
     */
    public function getSchedules(int $id): Collection
    {
        $tour = $this->find($id);
        if (! $tour) {
            return new Collection;
        }

        return $tour->schedules()
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Get ratings for a tour.
     * (Lấy đánh giá của tour)
     */
    public function getRatings(int $id, array $request): LengthAwarePaginator
    {
        $perPage = $request['per_page'] ?? Pagination::PER_PAGE->value;
        $tour = $this->find($id);

        if (! $tour) {
            return $this->model->newQuery()->whereRaw('1 = 0')->paginate($perPage);
        }

        $query = $tour->ratings()
            ->where('status', 'approved')
            ->with(['user', 'images']);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get star rating statistics for a tour.
     * (Lấy thống kê số sao đánh giá của một tour)
     */
    public function getRatingStats(int $id): array
    {
        $tour = $this->find($id);
        if (! $tour) {
            return array_fill(1, 5, 0);
        }

        $stats = $tour->ratings()
            ->where('status', 'approved')
            ->select('score', DB::raw('count(*) as count'))
            ->groupBy('score')
            ->get()
            ->pluck('count', 'score')
            ->toArray();

        // Ensure all star levels 1-5 are present
        $fullStats = [];
        for ($i = 1; $i <= 5; $i++) {
            $fullStats[$i] = $stats[$i] ?? 0;
        }

        return $fullStats;
    }

    /**
     * Get a tour schedule for a specific date.
     * (Lấy lịch khởi hành của tour cho một ngày cụ thể)
     */
    public function getScheduleByDate(int $id, string $date): ?TourSchedule
    {
        $tour = $this->find($id);
        if (! $tour) {
            return null;
        }

        return $tour->schedules()
            ->whereDate('start_date', $date)
            ->first();
    }

    /**
     * Update tour rating statistics.
     * (Cập nhật thống kê đánh giá của tour)
     */
    public function updateStats(int $id): bool
    {
        $tour = $this->find($id);
        if (! $tour) {
            return false;
        }

        $stats = $tour->ratings()
            ->where('status', 'approved')
            ->selectRaw('count(*) as count, avg(score) as average')
            ->first();

        return $tour->update([
            'rating_count' => $stats->count ?? 0,
            'rating_avg' => round(($stats->average ?? 0), 1),
        ]);
    }

    /**
     * Get tour data for export.
     * (Lấy dữ liệu tour để xuất)
     */
    public function getExportCollection(): Collection
    {
        return $this->model->with('category')->latest()->get();
    }

    /**
     * Get tour name suggestions by prefix.
     * (Lấy gợi ý tên tour theo tiền tố)
     *
     * @return string[]
     */
    public function getNameSuggestions(string $q, int $limit = 5): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        return $this->model->newQuery()
            ->where('status', 'available')
            ->where('name', 'like', $q.'%')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Get tours by IDs.
     * (Lấy danh sách tour theo mảng ID)
     *
     * @param  int[]  $ids
     */
    public function getByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection;
        }

        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->where('status', 'available')
            ->with(['category'])
            ->get();
    }
}
