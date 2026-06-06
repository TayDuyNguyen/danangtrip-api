<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Enums\TourStatus;
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
            $searchTerm = $filters['search'];
            $driver = $this->model->getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $query->whereFullText(['name', 'description', 'itinerary', 'inclusions', 'exclusions'], $searchTerm);
            } elseif ($driver === 'pgsql') {
                $query->whereRaw(
                    "to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(itinerary::text, '') || ' ' || coalesce(inclusions::text, '') || ' ' || coalesce(exclusions::text, '')) @@ plainto_tsquery('simple', ?)",
                    [$searchTerm]
                );
            } else {
                $query->where('name', 'like', '%'.$searchTerm.'%');
            }
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

        if (isset($filters['min_rating'])) {
            $query->where('rating_avg', '>=', $filters['min_rating']);
        }

        if (isset($filters['duration'])) {
            $query->where('duration', 'like', '%'.$filters['duration'].'%');
        }

        if (isset($filters['available_from']) || isset($filters['available_to'])) {
            $query->whereHas('schedules', function ($q) use ($filters) {
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

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (isset($filters['is_hot'])) {
            $query->where('is_hot', (bool) $filters['is_hot']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } elseif (empty($filters['for_admin'])) {
            $query->where('status', TourStatus::ACTIVE->value);
        }

        if (isset($filters['booking_availability'])) {
            $query->where(
                'booking_availability',
                $filters['booking_availability']
            );
        }

        $validSortFields = ['created_at', 'price_adult', 'view_count', 'name', 'rating_avg', 'booking_count'];
        $orderBy = in_array($filters['sort_by'] ?? '', $validSortFields) ? $filters['sort_by'] : 'created_at';
        $orderDir = in_array($filters['sort_order'] ?? '', ['asc', 'desc']) ? $filters['sort_order'] : 'desc';
        $query->orderBy($orderBy, $orderDir);

        $query->withCount('schedules');

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
            ->where('status', TourStatus::ACTIVE->value)
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
            ->where('status', TourStatus::ACTIVE->value)
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
                $query->where('start_date', '>', now()->toDateString())->orderBy('start_date', 'asc');
            }])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get schedules for a tour.
     * (Lấy lịch khởi hành của tour)
     */
    public function getSchedules(array $request): Collection
    {
        $tour = $this->find($request['id']);
        if (! $tour) {
            return new Collection;
        }

        $query = $tour->schedules()->orderBy('start_date', 'asc');

        if (! empty($request['from_date'])) {
            $today = now()->toDateString();
            $startDateLimit = $request['from_date'] <= $today ? now()->addDay()->toDateString() : $request['from_date'];
            $query->where('start_date', '>=', $startDateLimit);
        } else {
            $query->where('start_date', '>', now()->toDateString());
        }

        if (! empty($request['to_date'])) {
            $query->where('start_date', '<=', $request['to_date']);
        }

        return $query->get();
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
     * Get a tour schedule by ID.
     * (Lấy lịch khởi hành của tour theo ID)
     */
    public function getScheduleById(int $tourId, int $scheduleId): ?TourSchedule
    {
        $tour = $this->find($tourId);
        if (! $tour) {
            return null;
        }

        return $tour->schedules()
            ->where('id', $scheduleId)
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

        $driver = $this->model->getConnection()->getDriverName();
        $operator = $driver === 'pgsql' ? 'ilike' : 'like';
        $words = array_filter(explode(' ', $q), function ($word) {
            $cleaned = preg_replace('/[^\p{L}\p{N}]/u', '', $word);

            return mb_strlen($cleaned) >= 1;
        });

        $query = $this->model->newQuery()->where('status', TourStatus::ACTIVE->value);

        foreach ($words as $word) {
            if ($driver === 'pgsql') {
                $query->whereRaw('unaccent(name) ilike unaccent(?)', ['%'.$word.'%']);
            } else {
                $query->where('name', $operator, '%'.$word.'%');
            }
        }

        return $query->orderBy('view_count', 'desc')
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
            ->where('status', TourStatus::ACTIVE->value)
            ->with(['category'])
            ->get();
    }

    public function findAdminDetailById(int $id): ?Tour
    {
        return $this->model->newQuery()
            ->with([
                'category',
                'schedules' => function ($query) {
                    $query->orderBy('start_date', 'desc');
                },
            ])
            ->find($id);
    }

    public function syncLocations(int $tourId, array $locationIds): bool
    {
        $tour = $this->find($tourId);
        if (! $tour) {
            return false;
        }

        $tour->locations()->sync($locationIds);

        return true;
    }

    public function getUpcomingBookingAvailabilityValues(int $tourId): array
    {
        return TourSchedule::query()
            ->where('tour_id', $tourId)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->where('status', 'available')
            ->pluck('booking_availability')
            ->all();
    }

    public function updateBookingAvailability(int $tourId, string $availability): bool
    {
        $tour = $this->find($tourId);
        if (! $tour) {
            return false;
        }

        return $tour->update(['booking_availability' => $availability]);
    }
}
