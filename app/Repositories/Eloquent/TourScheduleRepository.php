<?php

namespace App\Repositories\Eloquent;

use App\Models\TourSchedule;
use App\Repositories\Interfaces\TourScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
        $query = $this->model->newQuery()->with(['tour.category']);

        $this->applyScheduleListFilters($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortField = $filters['sort'] ?? 'start_date';
        $sortOrder = strtolower((string) ($filters['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['start_date', 'end_date', 'max_people', 'booked_people', 'status', 'created_at'];
        if (! in_array($sortField, $allowedSort, true)) {
            $sortField = 'start_date';
        }

        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getStatusCounts(array $filters = []): array
    {
        $base = $this->model->newQuery();
        $this->applyScheduleListFilters($base, $filters, true);

        return [
            'total_schedules' => (clone $base)->count(),
            'available_schedules' => (clone $base)->where('status', 'available')->count(),
            'full_schedules' => (clone $base)->where('status', 'full')->count(),
            'cancelled_schedules' => (clone $base)->where('status', 'cancelled')->count(),
        ];
    }

    public function findWithTour(int $id): ?TourSchedule
    {
        return $this->model->newQuery()->with(['tour.category'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyScheduleListFilters(Builder $query, array $filters, bool $ignoreStatusFilter = false): void
    {
        if (! empty($filters['tour_id'])) {
            $query->where('tour_id', $filters['tour_id']);
        }

        if (! $ignoreStatusFilter && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('start_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('start_date', '<=', $filters['to']);
        }

        if (! empty($filters['q'])) {
            $keyword = (string) $filters['q'];
            $query->whereHas('tour', function (Builder $tourQuery) use ($keyword): void {
                $tourQuery->where('name', 'like', '%'.$keyword.'%');
            });
        }
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
