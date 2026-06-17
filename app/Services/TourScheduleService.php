<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\TourScheduleBookingAvailability;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;

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
        protected TourRepositoryInterface $tourRepository,
        protected TourStatusSyncService $tourStatusSyncService
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

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tour schedules',
            ];
        }
    }

    /**
     * Aggregated schedule counts for admin stats cards (filtered by tour/date/search, not by status).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getStatusCounts(array $filters): array
    {
        try {
            $counts = $this->tourScheduleRepository->getStatusCounts($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $counts,
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tour schedule stats',
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
            $schedule = $this->tourScheduleRepository->findWithTour($id);
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
            if (! array_key_exists('booking_availability', $data)) {
                $this->syncBookingAvailability($schedule);
            }
            $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $schedule,
            ];
        } catch (\Exception $e) {

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

            if (
                array_key_exists('max_people', $data)
                && (int) $data['max_people'] < (int) $schedule->booked_people
            ) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Max people cannot be less than the number of booked seats ('.$schedule->booked_people.').',
                ];
            }

            $updated = $this->tourScheduleRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update tour schedule',
                ];
            }
            $fresh = $this->tourScheduleRepository->find($id);
            if ($fresh && ! array_key_exists('booking_availability', $data)) {
                $this->syncBookingAvailability($fresh);
            }
            $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->tourScheduleRepository->find($id),
            ];
        } catch (\Exception $e) {

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
            $schedule = $this->tourScheduleRepository->find($id);
            if (! $schedule) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

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
            $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Tour schedule deleted successfully',
            ];
        } catch (\Exception $e) {

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
            $schedule = $this->tourScheduleRepository->find($id);
            if (! $schedule) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }

            $updated = $this->tourScheduleRepository->updateStatus($id, $status);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour schedule not found',
                ];
            }
            $this->tourStatusSyncService->syncByTourId((int) $schedule->tour_id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Status updated successfully',
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update status',
            ];
        }
    }

    private function syncBookingAvailability(TourSchedule $schedule): void
    {
        $target = $schedule->booked_people >= $schedule->max_people
            ? TourScheduleBookingAvailability::SOLD_OUT->value
            : TourScheduleBookingAvailability::OPEN->value;

        $current = $schedule->booking_availability instanceof \BackedEnum
            ? $schedule->booking_availability->value
            : (string) $schedule->booking_availability;

        if ($current !== $target) {
            $this->tourScheduleRepository->updateBookingAvailability((int) $schedule->id, $target);
        }
    }
}
