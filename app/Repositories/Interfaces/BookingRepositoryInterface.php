<?php

namespace App\Repositories\Interfaces;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all bookings with optional filters.
     */
    public function getBookings(array $filters = []): LengthAwarePaginator;

    /**
     * Find a booking by ID with its related tour schedule and user.
     */
    public function findWithDetails(int $id): ?Booking;

    /**
     * Find a booking by code.
     */
    public function findByCode(string $code): ?Booking;

    /**
     * Update the status of a booking.
     */
    public function updateStatus(int $id, string $status): bool;

    /**
     * Update the payment status of a booking.
     */
    public function updatePaymentStatus(int $id, string $paymentStatus): bool;

    /**
     * Get bookings for a specific user.
     */
    public function getUserBookings(int $userId, array $filters = []): LengthAwarePaginator;

    /**
     * Check if a tour schedule has any associated bookings.
     */
    public function hasBookings(int $tourScheduleId): bool;
}
