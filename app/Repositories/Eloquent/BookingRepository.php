<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Enums\Pagination;
use App\Models\Booking;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class BookingRepository
 * (Triển khai Repository cho Đặt chỗ)
 */
final class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    /**
     * Get the model instance.
     * (Lấy lớp Model)
     */
    public function getModel(): string
    {
        return Booking::class;
    }

    /**
     * Get all bookings with optional filters.
     * (Lấy danh sách đặt chỗ với bộ lọc tùy chọn)
     */
    public function getBookings(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('booking_code', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('user', function ($q2) use ($filters) {
                        $q2->where('name', 'like', '%'.$filters['search'].'%')
                            ->orWhere('email', 'like', '%'.$filters['search'].'%');
                    })
                    ->orWhereHas('items.tour', function ($q2) use ($filters) {
                        $q2->where('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        if (isset($filters['booking_status']) && $filters['booking_status'] !== 'all') {
            $query->where('booking_status', $filters['booking_status']);
        }

        if (isset($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('booked_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('booked_at', '<=', $filters['to_date']);
        }

        $perPage = (int) ($filters['per_page'] ?? Pagination::PER_PAGE->value);

        $allowedSorts = ['id', 'booking_code', 'total_amount', 'booked_at', 'created_at', 'booking_status', 'payment_status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = $query->with(['user', 'items.tour'])
            ->orderBy($sortBy, $sortOrder);

        if (isset($filters['no_paginate']) && $filters['no_paginate'] === true) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a booking by ID with its related tour schedule and user.
     * (Tìm một đơn đặt chỗ theo ID với lịch khởi hành tour và người dùng liên quan)
     */
    public function findWithDetails(int $id): ?Booking
    {
        return $this->model->newQuery()
            ->with(['user', 'items.tour', 'items.tourSchedule'])
            ->find($id);
    }

    /**
     * Find a booking by Code with its related items.
     * (Tìm một đơn đặt chỗ theo Mã với các mục liên quan)
     */
    public function findByCode(string $code): ?Booking
    {
        return $this->model->newQuery()
            ->with(['user', 'items.tour', 'items.tourSchedule', 'payments'])
            ->where('booking_code', $code)
            ->first();
    }

    /**
     * Update the status of a booking.
     * (Cập nhật trạng thái của đơn đặt chỗ)
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->update($id, ['booking_status' => $status]);
    }

    /**
     * Update the payment status of a booking.
     * (Cập nhật trạng thái thanh toán của đơn đặt chỗ)
     */
    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        return (bool) $this->update($id, ['payment_status' => $paymentStatus]);
    }

    /**
     * Get bookings for a specific user.
     * (Lấy danh sách đặt chỗ của một người dùng cụ thể)
     */
    public function getUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if (isset($filters['booking_status']) && $filters['booking_status'] !== 'all') {
            $query->where('booking_status', $filters['booking_status']);
        }

        if (isset($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        $perPage = (int) ($filters['per_page'] ?? Pagination::PER_PAGE->value);

        $allowedSorts = ['id', 'booking_code', 'total_amount', 'booked_at', 'created_at', 'booking_status', 'payment_status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? '') === 'asc' ? 'asc' : 'desc';

        return $query->with(['items.tour'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Check if a tour schedule has any associated bookings.
     * (Kiểm tra xem lịch khởi hành tour có đơn đặt chỗ nào liên quan không)
     */
    public function hasBookings(int $tourScheduleId): bool
    {
        return $this->model->whereHas('items', function ($q) use ($tourScheduleId) {
            $q->where('tour_schedule_id', $tourScheduleId);
        })
            ->whereIn('booking_status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
            ->exists();
    }

    /**
     * Get recent booked tour IDs by user.
     * (Lấy danh sách ID tour đã đặt gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentTourIds(int $userId, int $limit = 10): array
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereHas('items', function ($q) {
                $q->whereNotNull('tour_id');
            })
            ->with('items')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->pluck('items.*.tour_id')
            ->flatten()
            ->unique()
            ->all();
    }

    /**
     * Get booking trend grouped by date for the last N days.
     * (Lấy xu hướng đặt tour theo ngày trong N ngày gần nhất)
     */
    public function getBookingTrend(int $days): array
    {
        $table = $this->model->getTable();

        return $this->model->newQuery()
            ->selectRaw("CAST({$table}.booked_at AS DATE) as date, COUNT(*) as count")
            ->where('booked_at', '>=', now()->subDays($days)->startOfDay())
            ->groupByRaw("CAST({$table}.booked_at AS DATE)")
            ->orderByRaw("CAST({$table}.booked_at AS DATE)")
            ->get()
            ->toArray();
    }

    /**
     * Get booking report grouped by status and date.
     * (Lấy báo cáo đặt tour theo trạng thái và ngày)
     */
    public function getBookingReport(array $filters): array
    {
        $table = $this->model->getTable();
        $query = $this->model->newQuery()
            ->selectRaw("CAST({$table}.booked_at AS DATE) as date, booking_status, payment_status, COUNT(*) as count, SUM(total_amount) as total_amount");

        if (! empty($filters['from'])) {
            $query->whereDate('booked_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('booked_at', '<=', $filters['to']);
        }

        if (! empty($filters['status'])) {
            $query->where('booking_status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->groupByRaw("CAST({$table}.booked_at AS DATE), booking_status, payment_status")
            ->orderByRaw("CAST({$table}.booked_at AS DATE)")
            ->get()
            ->toArray();
    }

    /**
     * Get top tours by booking count.
     * (Lấy top tour theo số lượng đặt)
     */
    public function getTopTours(int $limit, ?string $from, ?string $to): array
    {
        $table = $this->model->getTable();

        return $this->model->newQuery()
            ->join('booking_items', "{$table}.id", '=', 'booking_items.booking_id')
            ->join('tours', 'booking_items.tour_id', '=', 'tours.id')
            ->selectRaw('
                tours.id, 
                tours.name, 
                tours.slug, 
                COUNT(DISTINCT booking_items.booking_id) as booking_count, 
                SUM(booking_items.subtotal) as total_revenue
            ')
            ->where("{$table}.booking_status", '!=', BookingStatus::CANCELLED->value)
            ->when($from, fn ($q) => $q->whereDate("{$table}.booked_at", '>=', $from))
            ->when($to, fn ($q) => $q->whereDate("{$table}.booked_at", '<=', $to))
            ->groupBy('tours.id', 'tours.name', 'tours.slug')
            ->orderByDesc('booking_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get total booking count.
     * (Lấy tổng số đơn đặt tour)
     */
    public function getTotalCount(): int
    {
        return $this->count();
    }

    /**
     * Get booking counts grouped by status.
     * (Lấy số lượng đơn đặt tour theo trạng thái)
     */
    public function getStatusCounts(array $filters = []): array
    {
        $query = $this->model->newQuery();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('booking_code', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('user', function ($q2) use ($filters) {
                        $q2->where('full_name', 'like', '%'.$filters['search'].'%')
                            ->orWhere('email', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('booked_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('booked_at', '<=', $filters['to_date']);
        }

        return $query->selectRaw('booking_status, COUNT(*) as count')
            ->groupBy('booking_status')
            ->get()
            ->pluck('count', 'booking_status')
            ->toArray();
    }
}
