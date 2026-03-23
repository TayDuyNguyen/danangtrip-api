<?php

namespace App\Repositories\Interfaces;

use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface LocationRepositoryInterface
 * Define standard operations for Location repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Địa điểm)
 */
interface LocationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get locations with filters and pagination.
     * (Lấy danh sách địa điểm với bộ lọc và phân trang)
     *
     * @param  array  $filters  Query filters.
     * @return LengthAwarePaginator Paginated locations list.
     */
    public function getLocations(array $filters = []): LengthAwarePaginator;

    /**
     * Get featured locations.
     * (Lấy địa điểm nổi bật)
     *
     * @param  int|null  $limit  Max items to return.
     * @return Collection Featured locations list.
     */
    public function getFeaturedLocations(?int $limit = null): Collection;

    /**
     * Get nearby locations.
     * (Lấy địa điểm gần vị trí hiện tại)
     *
     * @param  array  $data  Input data (lat/lng/radius/limit/sort).
     * @return Collection Nearby locations list.
     */
    public function getNearbyLocations(array $data): Collection;

    /**
     * Find a location by slug.
     * (Tìm địa điểm theo slug)
     *
     * @param  string  $slug  Location slug.
     * @return Location|null The location model or null.
     */
    public function findBySlug(string $slug): ?Location;

    /**
     * Get approved ratings for a location with pagination.
     * (Lấy đánh giá đã được phê duyệt cho một địa điểm với phân trang)
     *
     * @param  int  $id  Location id.
     * @param  array  $request  Pagination/sort parameters.
     * @return LengthAwarePaginator Paginated ratings list.
     */
    public function getApprovedRatings(int $id, array $request): LengthAwarePaginator;

    /**
     * Get location name suggestions by prefix.
     * (Lấy gợi ý tên địa điểm theo tiền tố)
     *
     * @param  string  $q  Name prefix.
     * @param  int  $limit  Max items to return.
     * @return string[] Suggested location names.
     */
    public function getNameSuggestions(string $q, int $limit = 5): array;
}
