<?php

namespace App\Repositories\Eloquent;

use App\Models\TourSchedule;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class TourScheduleRepository
 * Eloquent implementation of TourScheduleRepositoryInterface.
 * (Triển khai Eloquent cho TourScheduleRepositoryInterface)
 */
class TourScheduleRepository extends BaseRepository implements TourScheduleRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return TourSchedule::class;
    }

    /**
     * Get tour schedules with filters and pagination.
     * (Lấy danh sách lịch khởi hành với bộ lọc và phân trang)
     */
    public function getSchedules(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('tour');

        if (isset($filters['tour_id'])) {
            $query->where('tour_id', $filters['tour_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from'])) {
            $query->where('start_date', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('start_date', '<=', $filters['to']);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest('start_date')->paginate($perPage);
    }

    /**
     * Find a schedule by ID with tour relation.
     * (Tìm lịch khởi hành theo ID kèm theo quan hệ tour)
     */
    public function findWithTour(int $id): ?TourSchedule
    {
        return $this->model->with('tour')->find($id);
    }

    /**
     * Find a schedule by ID and lock for update.
     * (Tìm lịch khởi hành và lock để tránh conflict khi book)
     */
    public function findForUpdate(int $id): ?TourSchedule
    {
        return $this->model->with('tour')->lockForUpdate()->find($id);
    }

    /**
     * Update schedule status.
     * (Cập nhật trạng thái lịch khởi hành)
     */
    public function updateStatus(int $id, string $status): bool
    {
        $schedule = $this->find($id);
        if (! $schedule) {
            return false;
        }

        return $schedule->update(['status' => $status]);
    }

    /**
     * Check if schedule has any bookings.
     * (Kiểm tra xem lịch khởi hành có bất kỳ đơn đặt chỗ nào không)
     */
    public function hasBookings(int $id): bool
    {
        $schedule = $this->find($id);
        if (! $schedule) {
            return false;
        }

        return $schedule->bookingItems()->exists();
    }
}
