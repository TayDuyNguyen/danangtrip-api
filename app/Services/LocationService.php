<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\ViewRepositoryInterface;
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
     */
    public function __construct(
        protected LocationRepositoryInterface $locationRepository,
        protected ViewRepositoryInterface $viewRepository
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
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $locations,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
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
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Location not found'];
            }

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $location];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get location'];
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

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $locations];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get featured locations'];
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

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $locations];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get nearby locations'];
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
                // (Ghi nhận lượt xem thông qua ViewRepository)
                $this->viewRepository->create([
                    'location_id' => $id,
                    'session_id' => $sessionId,
                    'user_id' => $userId,
                    'created_at' => now(),
                ]);

                // (Tăng lượt xem thông qua LocationRepository)
                $this->locationRepository->incrementViewCount($id);
            });

            return ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'View recorded'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to record view'];
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
                'status' => HttpStatusCode::CREATED->value,
                'data' => $location,
            ];
        } catch (\Throwable $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
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
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Location not found'];
            }

            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->locationRepository->generateUniqueSlug($data['name'], 'slug', $id);
            }

            $this->locationRepository->update($id, $data);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $this->locationRepository->find($id)];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to update location'];
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

            return $deleted ? ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Deleted'] : ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Not found'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to delete location'];
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

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $ratings];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get location ratings'];
        }
    }

    /**
     * Get list of districts with active locations.
     * (Lấy danh sách các quận có địa điểm đang hoạt động)
     */
    public function getDistrictsWithLocations(): array
    {
        try {
            $districts = $this->locationRepository->getDistrictsWithLocations();

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $districts];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get districts'];
        }
    }

    /**
     * Get images for a location.
     * (Lấy danh sách ảnh của địa điểm)
     */
    public function getLocationImages(int $id): array
    {
        try {
            $location = $this->locationRepository->find($id);
            if (! $location) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Location not found'];
            }

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => ['images' => $location->images ?? []]];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get location images'];
        }
    }

    /**
     * Get rating statistics for a location.
     * (Lấy thống kê đánh giá của một địa điểm)
     */
    public function getLocationRatingStats(int $id): array
    {
        try {
            $stats = $this->locationRepository->getRatingStats($id);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $stats];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get rating stats'];
        }
    }

    /**
     * Get nearby locations relative to a location ID.
     * (Lấy các địa điểm lân cận theo ID địa điểm)
     */
    public function getNearbyLocationsByLocationId(int $id, int $limit): array
    {
        try {
            $locations = $this->locationRepository->getNearbyLocationsById($id, $limit);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $locations];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get nearby locations'];
        }
    }

    /**
     * Attach tags to a location.
     * (Gán tags cho địa điểm)
     */
    public function attachTags(int $id, array $tagIds): array
    {
        try {
            $this->locationRepository->attachTags($id, $tagIds);

            return ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Tags attached successfully'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to attach tags'];
        }
    }

    /**
     * Detach a tag from a location.
     * (Xóa tag khỏi địa điểm)
     */
    public function detachTag(int $id, int $tagId): array
    {
        try {
            $this->locationRepository->detachTag($id, $tagId);

            return ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Tag detached successfully'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to detach tag'];
        }
    }

    /**
     * Attach amenities to a location.
     * (Gán tiện ích cho địa điểm)
     */
    public function attachAmenities(int $id, array $amenityIds): array
    {
        try {
            $this->locationRepository->attachAmenities($id, $amenityIds);

            return ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Amenities attached successfully'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to attach amenities'];
        }
    }

    /**
     * Detach an amenity from a location.
     * (Xóa tiện ích khỏi địa điểm)
     */
    public function detachAmenity(int $id, int $amenityId): array
    {
        try {
            $this->locationRepository->detachAmenity($id, $amenityId);

            return ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Amenity detached successfully'];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to detach amenity'];
        }
    }

    /**
     * Get data for export.
     * (Lấy dữ liệu để xuất bản)
     */
    public function getExportData(): array
    {
        try {
            $data = $this->locationRepository->getExportData();

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $data];
        } catch (\Exception $_) {
            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get export data'];
        }
    }
}
