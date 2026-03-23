<?php

namespace App\Services;

use App\Models\View;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class LocationService
 * Handles business logic related to locations.
 * (Xử lý logic nghiệp vụ liên quan đến địa điểm)
 */
final class LocationService
{
    /**
     * LocationService constructor.
     * (Khởi tạo LocationService)
     *
     * @return void
     */
    public function __construct(
        protected LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Get list of locations with filters.
     * (Lấy danh sách địa điểm với bộ lọc)
     */
    public function getLocations(array $filters): array
    {
        try {
            $locations = $this->locationRepository->getLocations($filters);

            return [
                'status' => 200,
                'data' => $locations,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to get locations',
            ];
        }
    }

    /**
     * Get location detail by slug.
     * (Lấy chi tiết địa điểm theo slug)
     */
    public function getLocationBySlug(string $slug): array
    {
        try {
            $location = $this->locationRepository->findBySlug($slug);
            if (! $location) {
                return ['status' => 404, 'message' => 'Location not found'];
            }

            return ['status' => 200, 'data' => $location];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to get location'];
        }
    }

    /**
     * Get featured locations.
     * (Lấy địa điểm nổi bật)
     */
    public function getFeaturedLocations(?int $limit = null): array
    {
        try {
            $locations = $this->locationRepository->getFeaturedLocations($limit);

            return ['status' => 200, 'data' => $locations];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to get featured locations'];
        }
    }

    /**
     * Get nearby locations.
     * (Lấy địa điểm gần vị trí hiện tại)
     */
    public function getNearbyLocations(array $data): array
    {
        try {
            $locations = $this->locationRepository->getNearbyLocations($data);

            return ['status' => 200, 'data' => $locations];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to get nearby locations'];
        }
    }

    /**
     * Record a view for a location.
     * (Ghi lại lượt xem cho một địa điểm)
     */
    public function recordView(int $id, string $sessionId, ?int $userId = null): array
    {
        try {
            DB::transaction(function () use ($id, $sessionId, $userId) {
                View::create([
                    'location_id' => $id,
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'created_at' => now(),
                ]);

                $this->locationRepository->getQuery()->where('id', $id)->increment('view_count');
            });

            return ['status' => 200, 'message' => 'View recorded'];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to record view'];
        }
    }

    /**
     * Create new location (Admin).
     * (Tạo địa điểm mới - Admin)
     */
    public function createLocation(array $data): array
    {
        try {
            if (empty($data['slug'])) {
                $data['slug'] = $this->locationRepository->generateUniqueSlug($data['name']);
            }

            $location = $this->locationRepository->create($data);

            return [
                'status' => 201,
                'data' => $location,
            ];
        } catch (\Throwable $_) {
            return [
                'status' => 500,
                'message' => 'Failed to create location',
            ];
        }
    }

    /**
     * Update location (Admin).
     * (Cập nhật địa điểm - Admin)
     */
    public function updateLocation(int $id, array $data): array
    {
        try {
            $location = $this->locationRepository->find($id);
            if (! $location) {
                return ['status' => 404, 'message' => 'Location not found'];
            }

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->locationRepository->generateUniqueSlug($data['name'], 'slug', $id);
            }

            $this->locationRepository->update($id, $data);

            return ['status' => 200, 'data' => $this->locationRepository->find($id)];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to update location'];
        }
    }

    /**
     * Delete location (Admin).
     * (Xóa địa điểm - Admin)
     */
    public function deleteLocation(int $id): array
    {
        try {
            $deleted = $this->locationRepository->delete($id);

            return $deleted ? ['status' => 200, 'message' => 'Deleted'] : ['status' => 404, 'message' => 'Not found'];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to delete location'];
        }
    }

    /**
     * Get approved ratings for a location.
     * (Lấy đánh giá đã được phê duyệt cho một địa điểm)
     */
    public function getLocationRatings(int $id, array $request): array
    {
        try {
            $ratings = $this->locationRepository->getApprovedRatings($id, $request);

            return ['status' => 200, 'data' => $ratings];
        } catch (\Exception $_) {
            return ['status' => 500, 'message' => 'Failed to get location ratings'];
        }
    }
}
