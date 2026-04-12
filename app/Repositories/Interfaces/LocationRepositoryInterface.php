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
     * Get a list of districts that have at least one location.
     * (Lấy danh sách các quận có ít nhất một địa điểm)
     */
    public function getDistrictsWithLocations(): array;

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

    /**
     * Increment favorite count of a location.
     * (Tăng số lượng yêu thích của một địa điểm)
     */
    public function incrementFavoriteCount(int $id): bool;

    /**
     * Decrement favorite count of a location.
     * (Giảm số lượng yêu thích của một địa điểm)
     */
    public function decrementFavoriteCount(int $id): bool;

    /**
     * Increment view count of a location.
     * (Tăng số lượng lượt xem của một địa điểm)
     */
    public function incrementViewCount(int $id): bool;

    /**
     * Get locations by IDs.
     * (Lấy danh sách địa điểm theo mảng ID)
     *
     * @param  int[]  $ids
     */
    public function getByIds(array $ids): Collection;

    /**
     * Get total view count across all locations.
     * (Lấy tổng lượt xem của tất cả địa điểm)
     */
    public function getTotalViewCount(): int;

    /**
     * Get location stats grouped by category and district.
     * (Lấy thống kê địa điểm theo danh mục và quận)
     */
    public function getStatsByCategoryAndDistrict(?string $fromDate = null, ?string $toDate = null): array;

    /**
     * Get star rating statistics for a location.
     * (Lấy thống kê số sao đánh giá của một địa điểm)
     */
    public function getRatingStats(int $id): array;

    /**
     * Get nearby locations relative to a specific location.
     * (Lấy các địa điểm lân cận tương đối với một địa điểm cụ thể)
     */
    public function getNearbyLocationsById(int $id, int $limit): Collection;

    /**
     * Attach or sync tags to a location.
     * (Gán tags cho địa điểm)
     */
    public function attachTags(int $id, array $tagIds): void;

    /**
     * Detach a specific tag from a location.
     * (Xóa tag khỏi địa điểm)
     */
    public function detachTag(int $id, int $tagId): void;

    /**
     * Attach or sync amenities to a location.
     * (Gán tiện ích cho địa điểm)
     */
    public function attachAmenities(int $id, array $amenityIds): void;

    /**
     * Detach a specific amenity from a location.
     * (Xóa tiện ích khỏi địa điểm)
     */
    public function detachAmenity(int $id, int $amenityId): void;

    /**
     * Update location rating statistics by calculating from approved ratings.
     * (Cập nhật thống kê đánh giá của địa điểm bằng cách tính toán từ các đánh giá đã duyệt)
     *
     * @param  int  $id  Location id.
     * @return bool True if updated.
     */
    public function updateStats(int $id): bool;

    /**
     * Get location data for export.
     * (Lấy dữ liệu địa điểm để xuất bản)
     */
    public function getExportData(): Collection;

    /**
     * Get top locations ordered by favorite and view count.
     * (Lấy top địa điểm theo lượt yêu thích và lượt xem)
     */
    public function getTopLocations(int $limit): Collection;
}
