<?php

namespace App\Repositories\Eloquent;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingRepository extends BaseRepository implements BookingRepositoryInterface
{
    /**
     * Get the model instance.
     */
    public function getModel(): string
    {
        return Booking::class;
    }

    /**
     * Get all bookings with optional filters.
     */
    public function getBookings(array $filters = []): LengthAwarePaginator
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

        $perPage = $filters['per_page'] ?? 10;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->with(['user', 'items.tour'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Find a booking by ID with its related tour schedule and user.
     */
    public function findWithDetails(int $id): ?Booking
    {
        return $this->model->with(['user', 'items.tour', 'items.tourSchedule'])->find($id);
    }

    /**
     * Find a booking by Code with its related items.
     */
    public function findByCode(string $code): ?Booking
    {
        return $this->model->with(['user', 'items.tour', 'items.tourSchedule', 'payments'])
            ->where('booking_code', $code)
            ->first();
    }

    /**
     * Update the status of a booking.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $booking = $this->find($id);
        if ($booking) {
            $booking->booking_status = $status;

            return $booking->save();
        }

        return false;
    }

    /**
     * Update the payment status of a booking.
     */
    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        $booking = $this->find($id);
        if ($booking) {
            $booking->payment_status = $paymentStatus;

            return $booking->save();
        }

        return false;
    }

    /**
     * Get bookings for a specific user.
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

        $perPage = $filters['per_page'] ?? 10;
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->with(['items.tour'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    /**
     * Check if a tour schedule has any associated bookings.
     */
    public function hasBookings(int $tourScheduleId): bool
    {
        return $this->model->whereHas('items', function ($q) use ($tourScheduleId) {
            $q->where('tour_schedule_id', $tourScheduleId);
        })
            ->whereIn('booking_status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
            ->exists();
    }
}
