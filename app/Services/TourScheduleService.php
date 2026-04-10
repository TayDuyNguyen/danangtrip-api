<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Tour;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class TourScheduleService
 * Handles business logic related to tour schedules.
 * (Xử lý logic nghiệp vụ liên quan đến lịch khởi hành tour)
 */
final class TourScheduleService
{
    /**
     * TourScheduleService constructor.
     * (Khởi tạo TourScheduleService)
     */
    public function __construct(
        protected TourScheduleRepositoryInterface $tourScheduleRepository,
        protected TourRepositoryInterface $tourRepository
    ) {}

    /**
     * Get list of tour schedules with filters.
     * (Lấy danh sách lịch khởi hành với bộ lọc)
     */
    public function getSchedules(array $filters): array
    {
        try {
            $schedules = $this->tourScheduleRepository->getSchedules($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $schedules,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tour schedules',
            ];
        }
    }

    /**
     * Get tour schedule detail by ID.
     * (Lấy chi tiết lịch khởi hành theo ID)
     */
    public function getScheduleById(int $id): array
    {
        try {
            $schedule = $this->tourScheduleRepository->with('tour')->find($id);
            if (! $schedule) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $schedule,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tour schedule',
            ];
        }
    }

    /**
     * Create a new tour schedule.
     * (Tạo lịch khởi hành mới)
     */
    public function createSchedule(array $data): array
    {
        try {
            // Handle fallback prices from tour if not provided
            if (empty($data['price_adult']) || empty($data['price_child']) || empty($data['price_infant'])) {
                $tour = $this->tourRepository->find($data['tour_id']);
                if ($tour instanceof Tour) {
                    $data['price_adult'] = $data['price_adult'] ?? $tour->price_adult;
                    $data['price_child'] = $data['price_child'] ?? $tour->price_child;
                    $data['price_infant'] = $data['price_infant'] ?? $tour->price_infant;
                }
            }

            $schedule = $this->tourScheduleRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $schedule,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create tour schedule',
            ];
        }
    }

    /**
     * Update an existing tour schedule.
     * (Cập nhật lịch khởi hành)
     */
    public function updateSchedule(int $id, array $data): array
    {
        try {
            $schedule = $this->tourScheduleRepository->find($id);
            if (! $schedule) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

            // Business logic: Ensure end_date is not before start_date
            $startDate = $data['start_date'] ?? $schedule->start_date->format('Y-m-d');
            $endDate = $data['end_date'] ?? $schedule->end_date->format('Y-m-d');

            if ($endDate < $startDate) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'The end date must be after or equal to start date.',
                ];
            }

            $updated = $this->tourScheduleRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update tour schedule',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->tourScheduleRepository->find($id),
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update tour schedule',
            ];
        }
    }

    /**
     * Delete a tour schedule.
     * (Xóa lịch khởi hành)
     */
    public function deleteSchedule(int $id): array
    {
        try {
            if ($this->tourScheduleRepository->hasBookings($id)) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Cannot delete schedule with existing bookings',
                ];
            }

            $deleted = $this->tourScheduleRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Tour schedule deleted successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete tour schedule',
            ];
        }
    }

    /**
     * Update tour schedule status.
     * (Cập nhật trạng thái lịch khởi hành)
     */
    public function updateStatus(int $id, string $status): array
    {
        try {
            $updated = $this->tourScheduleRepository->updateStatus($id, $status);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Status updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update status',
            ];
        }
    }
}
