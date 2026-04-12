<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\AmenityRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class AmenityService
 * (Dịch vụ xử lý các hoạt động cho Tiện ích)
 */
final class AmenityService
{
    /**
     * AmenityService constructor.
     * (Khởi tạo AmenityService)
     */
    public function __construct(
        protected AmenityRepositoryInterface $amenityRepository
    ) {}

    /**
     * Get all amenities.
     * (Lấy tất cả tiện ích)
     */
    public function getAllAmenities(array $filters): array
    {
        try {
            $category = $filters['category'] ?? null;
            $amenities = $this->amenityRepository->getAll($category);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $amenities,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve amenities.',
            ];
        }
    }

    /**
     * Create a new amenity (Admin).
     * (Tạo tiện ích mới - Admin)
     */
    public function createAmenity(array $data): array
    {
        try {
            $amenity = $this->amenityRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $amenity,
                'message' => 'Amenity created successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create amenity.',
            ];
        }
    }

    /**
     * Update an existing amenity (Admin).
     * (Cập nhật tiện ích - Admin)
     */
    public function updateAmenity(int $id, array $data): array
    {
        try {
            $amenity = $this->amenityRepository->find($id);

            if (! $amenity) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Amenity not found.',
                ];
            }

            $this->amenityRepository->update($id, $data);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->amenityRepository->find($id),
                'message' => 'Amenity updated successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update amenity.',
            ];
        }
    }

    /**
     * Delete an amenity (Admin).
     * (Xóa tiện ích - Admin)
     */
    public function deleteAmenity(int $id): array
    {
        try {
            $amenity = $this->amenityRepository->find($id);

            if (! $amenity) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Amenity not found.',
                ];
            }

            $this->amenityRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Amenity deleted successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete amenity.',
            ];
        }
    }
}
