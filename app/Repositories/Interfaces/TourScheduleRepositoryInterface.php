<?php

namespace App\Repositories\Interfaces;

use App\Models\TourSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface TourScheduleRepositoryInterface
 * Define standard operations for TourSchedule repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Lịch khởi hành tour)
 */
interface TourScheduleRepositoryInterface extends RepositoryInterface
{
    /**
     * Get tour schedules with filters and pagination.
     * (Lấy danh sách lịch khởi hành với bộ lọc và phân trang)
     */
    public function getSchedules(array $filters = []): LengthAwarePaginator;

    /**
     * Count schedules by status for dashboard cards (respects tour/date/search filters, not status).
     *
     * @return array{total_schedules: int, available_schedules: int, full_schedules: int, cancelled_schedules: int}
     */
    public function getStatusCounts(array $filters = []): array;

    /**
     * Find schedule by ID with tour (and category) eager-loaded for API detail.
     */
    public function findWithTour(int $id): ?TourSchedule;

    /**
     * Find a schedule by ID for update.
     * (Tìm lịch khởi hành theo ID để cập nhật)
     */
    public function findForUpdate(int $id): ?TourSchedule;

    /**
     * Update schedule status.
     * (Cập nhật trạng thái lịch khởi hành)
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Check if schedule has any bookings.
     * (Kiểm tra xem lịch khởi hành có bất kỳ đơn đặt chỗ nào không)
     */
    public function hasBookings(int $id): bool;

    public function increaseBookedPeople(int $id, int $amount): bool;

    public function decreaseBookedPeople(int $id, int $amount): bool;

    public function updateBookingAvailability(int $id, string $availability): bool;
}
