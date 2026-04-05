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
     * Get a list of districts that have at least one location.
     * (Lấy danh sách các quận có ít nhất một địa điểm)
     */
    public function getDistrictsWithLocations(): array
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->distinct()
            ->pluck('district')
            ->filter()
            ->values()
            ->all();
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
            return new LengthAwarePaginator([], 0, $request['per_page'] ?? Pagination::PER_PAGE->value);
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

    /**
     * Increment favorite count of a location.
     * (Tăng số lượng yêu thích của một địa điểm)
     */
    public function incrementFavoriteCount(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->increment('favorite_count');
    }

    /**
     * Decrement favorite count of a location.
     * (Giảm số lượng yêu thích của một địa điểm)
     */
    public function decrementFavoriteCount(int $id): bool
    {
        return (bool) $this->model->where('id', $id)
            ->where('favorite_count', '>', 0)
            ->decrement('favorite_count');
    }

    /**
     * Increment view count of a location.
     * (Tăng số lượng lượt xem của một địa điểm)
     */
    public function incrementViewCount(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->increment('view_count');
    }

    /**
     * Get total location count.
     * (Lấy tổng số địa điểm)
     */
    public function getTotalCount(): int
    {
        return $this->model->count();
    }

    /**
     * Get total view count across all locations.
     * (Lấy tổng lượt xem của tất cả địa điểm)
     */
    public function getTotalViewCount(): int
    {
        return (int) $this->model->sum('view_count');
    }

    /**
     * Get location stats grouped by category and district.
     * (Lấy thống kê địa điểm theo danh mục và quận)
     */
    public function getStatsByCategoryAndDistrict(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = $this->model->newQuery()
            ->selectRaw('category_id, district, COUNT(*) as count')
            ->with('category:id,name');

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->groupBy('category_id', 'district')
            ->get()
            ->toArray();
    }

    /**
     * Get star rating statistics for a location.
     * (Lấy thống kê số sao đánh giá của một địa điểm)
     */
    public function getRatingStats(int $id): array
    {
        $stats = $this->model->newQuery()
            ->join('ratings', 'locations.id', '=', 'ratings.location_id')
            ->where('locations.id', $id)
            ->where('ratings.status', 'approved')
            ->selectRaw('ratings.rating, count(*) as count')
            ->groupBy('ratings.rating')
            ->pluck('count', 'rating')
            ->all();

        // Fill missing stars with 0
        $result = [];
        for ($i = 5; $i >= 1; $i--) {
            $result[$i] = $stats[$i] ?? 0;
        }

        return $result;
    }

    /**
     * Get nearby locations relative to a specific location.
     * (Lấy các địa điểm lân cận tương đối với một địa điểm cụ thể)
     */
    public function getNearbyLocationsById(int $id, int $limit): Collection
    {
        $location = $this->find($id);
        if (! $location || ! $location->latitude || ! $location->longitude) {
            return new Collection;
        }

        return $this->getNearbyLocations([
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'radius' => 5, // 5km default for recommendations
            'limit' => $limit + 1, // +1 to exclude self
        ])->reject(fn ($item) => $item->id === $id)->take($limit);
    }

    /**
     * Attach or sync tags to a location.
     * (Gán tags cho địa điểm)
     */
    public function attachTags(int $id, array $tagIds): void
    {
        $location = $this->find($id);
        if ($location) {
            $location->tags()->sync($tagIds);
        }
    }

    /**
     * Detach a specific tag from a location.
     * (Xóa tag khỏi địa điểm)
     */
    public function detachTag(int $id, int $tagId): void
    {
        $location = $this->find($id);
        if ($location) {
            $location->tags()->detach($tagId);
        }
    }

    /**
     * Attach or sync amenities to a location.
     * (Gán tiện ích cho địa điểm)
     */
    public function attachAmenities(int $id, array $amenityIds): void
    {
        $location = $this->find($id);
        if ($location) {
            $location->amenities()->sync($amenityIds);
        }
    }

    /**
     * Detach a specific amenity from a location.
     * (Xóa tiện ích khỏi địa điểm)
     */
    public function detachAmenity(int $id, int $amenityId): void
    {
        $location = $this->find($id);
        if ($location) {
            $location->amenities()->detach($amenityId);
        }
    }

    /**
     * Update location rating statistics by calculating from approved ratings.
     * (Cập nhật thống kê đánh giá của địa điểm bằng cách tính toán từ các đánh giá đã duyệt)
     *
     * @param  int  $id  Location id.
     * @return bool True if updated.
     */
    public function updateStats(int $id): bool
    {
        $stats = $this->model->newQuery()
            ->join('ratings', 'locations.id', '=', 'ratings.location_id')
            ->where('locations.id', $id)
            ->where('ratings.status', 'approved')
            ->selectRaw('COUNT(ratings.id) as count, AVG(ratings.score) as avg')
            ->first();

        return (bool) $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->update([
                'review_count' => $stats->count ?? 0,
                'avg_rating' => round(($stats->avg ?? 0), 1),
                'updated_at' => now(),
            ]);
    }

    /**
     * Get location data for export.
     * (Lấy dữ liệu địa điểm để xuất bản)
     */
    public function getExportData(): array
    {
        return $this->model->newQuery()
            ->with(['category:id,name', 'subcategory:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'ID' => $item->id,
                'Name' => $item->name,
                'Slug' => $item->slug,
                'Category' => $item->category?->name,
                'Subcategory' => $item->subcategory?->name,
                'District' => $item->district,
                'Address' => $item->address,
                'Phone' => $item->phone,
                'Avg Rating' => $item->avg_rating,
                'Review Count' => $item->review_count,
                'View Count' => $item->view_count,
                'Status' => $item->status,
                'Created At' => $item->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }
}
