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
     */
    public function getLocations(array $filters = []): LengthAwarePaginator;

    /**
     * Get featured locations.
     * (Lấy địa điểm nổi bật)
     */
    public function getFeaturedLocations(?int $limit = null): Collection;

    /**
     * Get nearby locations.
     * (Lấy địa điểm gần vị trí hiện tại)
     */
    public function getNearbyLocations(array $data): Collection;

    /**
     * Find a location by slug.
     * (Tìm địa điểm theo slug)
     */
    public function findBySlug(string $slug): ?Location;

    /**
     * Get approved ratings for a location with pagination.
     * (Lấy đánh giá đã được phê duyệt cho một địa điểm với phân trang)
     */
    public function getApprovedRatings(int $id, array $request): LengthAwarePaginator;
}
