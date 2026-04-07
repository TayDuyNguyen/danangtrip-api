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
     * Find a schedule by ID with tour relation.
     * (Tìm lịch khởi hành theo ID kèm theo quan hệ tour)
     */
    public function findWithTour(int $id): ?TourSchedule;

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
}
