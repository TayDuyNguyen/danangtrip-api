<?php

namespace App\Repositories\Eloquent;

use App\Enums\Constants;
use App\Enums\Pagination;
use App\Models\Location;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class LocationRepository
 * Eloquent implementation of LocationRepositoryInterface.
 * (Thực thi Eloquent cho LocationRepositoryInterface)
 */
class LocationRepository extends BaseRepository implements LocationRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Location::class;
    }

    /**
     * Get locations with filters and pagination.
     * (Lấy danh sách địa điểm với bộ lọc và phân trang)
     *
     * @param  array  $filters  Query filters.
     * @return LengthAwarePaginator Paginated locations list.
     */
    public function getLocations(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('status', 'active')
            ->with(['category', 'subcategory', 'tags']);

        if (isset($filters['q']) && ! isset($filters['search'])) {
            $filters['search'] = $filters['q'];
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        if (isset($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (isset($filters['price_level'])) {
            $query->where('price_level', $filters['price_level']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price_min', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price_max', '<=', $filters['price_max']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['rating_min'])) {
            $query->where('avg_rating', '>=', $filters['rating_min']);
        }

        if (isset($filters['tag'])) {
            $tags = is_array($filters['tag'])
                ? $filters['tag']
                : array_filter(array_map('trim', explode(',', (string) $filters['tag'])));

            if (count($tags) > 0) {
                $query->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('slug', $tags)->orWhereIn('name', $tags);
                });
            }
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $driver = $this->model->getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $query->whereFullText(['name', 'address', 'description', 'short_description'], $searchTerm);
            } else {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('address', 'like', "%{$searchTerm}%");
                });
            }
        }

        if (isset($filters['sort']) && ! isset($filters['sort_by'])) {
            $filters['sort_by'] = $filters['sort'];
        }

        if (isset($filters['order']) && ! isset($filters['sort_order'])) {
            $filters['sort_order'] = $filters['order'];
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get location name suggestions by prefix.
     * (Lấy gợi ý tên địa điểm theo tiền tố)
     *
     * @param  string  $q  Name prefix.
     * @param  int  $limit  Max items to return.
     * @return string[] Suggested location names.
     */
    public function getNameSuggestions(string $q, int $limit = 5): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        return $this->model->newQuery()
            ->where('status', 'active')
            ->where('name', 'like', $q.'%')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Get featured locations.
     * (Lấy địa điểm nổi bật)
     */
    public function getFeaturedLocations(?int $limit = null): Collection
    {
        $limit = $limit ?? Constants::LIMIT;

        return $this->model->where('status', 'active')
            ->where('is_featured', true)
            ->orderBy('avg_rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get nearby locations using Haversine formula.
     * (Lấy địa điểm gần đó sử dụng công thức Haversine)
     */
    public function getNearbyLocations(array $data): Collection
    {
        $lat = $data['lat'];
        $lng = $data['lng'];
        $radius = $data['radius'] ?? Constants::RADIUS;
        $limit = $data['limit'] ?? Constants::LIMIT_FEATURED;
        $sortBy = $data['sort_by'] ?? Constants::SORT_BY_NEARBY;
        $sortOrder = $data['sort_order'] ?? Constants::SORT_ORDER_NEARBY;

        // Haversine formula
        // Distance = 6371 * 2 * ASIN(SQRT(POWER(SIN((lat - lat2) * PI()/360), 2) + COS(lat * PI()/180) * COS(lat2 * PI()/180) * POWER(SIN((lng - lng2) * PI()/360), 2)))

        return $this->model->select('*')
            ->selectRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance', [$lat, $lng, $lat])
            ->where('status', 'active')
            ->having('distance', '<=', $radius)
            ->orderBy($sortBy, $sortOrder)
            ->limit($limit)
            ->get();
    }

    /**
     * Find a location by slug.
     * (Tìm địa điểm theo slug)
     */
    public function findBySlug(string $slug): ?Location
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get approved ratings for a location with pagination.
     * (Lấy đánh giá đã được phê duyệt cho một địa điểm với phân trang)
     */
    public function getApprovedRatings(int $id, array $request): LengthAwarePaginator
    {
        $location = $this->find($id);

        if (! $location) {
            return new LengthAwarePaginator([], 0, $request['per_page'] ?? 10);
        }

        return $location->approvedRatings()
            ->with(['user', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(
                $request['per_page'] ?? Pagination::PER_PAGE->value,
                ['*'],
                'page',
                $request['page'] ?? Pagination::PAGE->value
            );
    }
}
