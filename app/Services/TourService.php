<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\TourRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class TourService
 * Handles business logic related to tours.
 * (Xử lý logic nghiệp vụ liên quan đến tour)
 */
final class TourService
{
    /**
     * TourService constructor.
     * (Khởi tạo TourService)
     */
    public function __construct(
        protected TourRepositoryInterface $tourRepository
    ) {}

    /**
     * Get list of tours with filters.
     * (Lấy danh sách tour với bộ lọc)
     */
    public function getTours(array $filters): array
    {
        try {
            $tours = $this->tourRepository->getTours($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $tours,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tours',
            ];
        }
    }

    /**
     * Get featured tours.
     * (Lấy tour nổi bật)
     */
    public function getFeaturedTours(?int $limit = null): array
    {
        try {
            $tours = $this->tourRepository->getFeaturedTours($limit);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $tours];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get featured tours'];
        }
    }

    /**
     * Get hot tours.
     * (Lấy tour hot)
     */
    public function getHotTours(?int $limit = null): array
    {
        try {
            $tours = $this->tourRepository->getHotTours($limit);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $tours];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get hot tours'];
        }
    }

    /**
     * Get tour detail by slug.
     * (Lấy chi tiết tour theo slug)
     */
    public function getTourBySlug(string $slug): array
    {
        try {
            $tour = $this->tourRepository->findBySlug($slug);
            if (! $tour) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Tour not found'];
            }

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $tour];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get tour detail'];
        }
    }

    /**
     * Get schedules for a tour.
     * (Lấy lịch khởi hành của tour)
     */
    public function getSchedules(int $id): array
    {
        try {
            $schedules = $this->tourRepository->getSchedules($id);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $schedules];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get tour schedules'];
        }
    }

    /**
     * Get ratings for a tour.
     * (Lấy đánh giá của tour)
     */
    public function getRatings(int $id, array $request): array
    {
        try {
            $ratings = $this->tourRepository->getRatings($id, $request);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $ratings];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get tour ratings'];
        }
    }

    /**
     * Get rating stats for a tour.
     * (Lấy thống kê đánh giá của tour)
     */
    public function getRatingStats(int $id): array
    {
        try {
            $stats = $this->tourRepository->getRatingStats($id);

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $stats];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to get rating stats'];
        }
    }

    /**
     * Check availability for a tour.
     * (Kiểm tra còn chỗ cho tour)
     */
    public function checkAvailability(int $id, string $date): array
    {
        try {
            $isAvailable = $this->tourRepository->checkAvailability($id, $date);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['is_available' => $isAvailable],
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to check availability'];
        }
    }

    /**
     * Create tour (Admin).
     * (Tạo tour - Admin)
     */
    public function createTour(array $data): array
    {
        try {
            $tour = $this->tourRepository->create($data);

            return ['status' => HttpStatusCode::CREATED->value, 'data' => $tour];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to create tour'];
        }
    }

    /**
     * Update tour (Admin).
     * (Cập nhật tour - Admin)
     */
    public function updateTour(int $id, array $data): array
    {
        try {
            $updated = $this->tourRepository->update($id, $data);
            if (! $updated) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Tour not found'];
            }

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $this->tourRepository->find($id)];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to update tour'];
        }
    }

    /**
     * Delete tour (Admin).
     * (Xóa tour - Admin)
     */
    public function deleteTour(int $id): array
    {
        try {
            $deleted = $this->tourRepository->delete($id);

            return $deleted ? ['status' => HttpStatusCode::SUCCESS->value, 'message' => 'Tour deleted successfully'] : ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Tour not found'];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to delete tour'];
        }
    }

    /**
     * Update tour status (Admin).
     * (Cập nhật trạng thái tour - Admin)
     */
    public function updateStatus(int $id, string $status): array
    {
        return $this->updateTour($id, ['status' => $status]);
    }

    /**
     * Toggle featured status (Admin).
     * (Bật/tắt nổi bật - Admin)
     */
    public function toggleFeatured(int $id): array
    {
        try {
            $tour = $this->tourRepository->find($id);
            if (! $tour) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Tour not found'];
            }

            return $this->updateTour($id, ['is_featured' => ! $tour->is_featured]);
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to toggle featured status'];
        }
    }

    /**
     * Toggle hot status (Admin).
     * (Bật/tắt tour hot - Admin)
     */
    public function toggleHot(int $id): array
    {
        try {
            $tour = $this->tourRepository->find($id);
            if (! $tour) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Tour not found'];
            }

            return $this->updateTour($id, ['is_hot' => ! $tour->is_hot]);
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to toggle hot status'];
        }
    }

    /**
     * Export tours (Admin).
     * (Xuất danh sách tour - Admin)
     */
    public function exportTours(): array
    {
        try {
            $data = $this->tourRepository->getExportCollection();

            return ['status' => HttpStatusCode::SUCCESS->value, 'data' => $data];
        } catch (\Exception $e) {
            Log::error($e);

            return ['status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value, 'message' => 'Failed to export tours'];
        }
    }
}
