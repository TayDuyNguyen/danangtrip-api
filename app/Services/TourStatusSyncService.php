<?php

namespace App\Services;

use App\Enums\TourBookingAvailability;
use App\Enums\TourScheduleBookingAvailability;
use App\Enums\TourStatus;
use App\Repositories\Interfaces\TourRepositoryInterface;

/**
 * Sync tour.booking_availability from upcoming tour_schedules rows.
 * Uses schedule operational status (available) and booking_availability (open/sold_out);
 * legacy schedule status "full" is no longer written after migrations.
 * Tour status (active/inactive) is admin-controlled; inactive tours are not auto-changed.
 */
final class TourStatusSyncService
{
    public function __construct(
        protected TourRepositoryInterface $tourRepository
    ) {}

    public function syncByTourId(int $tourId): void
    {
        $tour = $this->tourRepository->find($tourId);
        if (! $tour) {
            return;
        }

        if ($tour->status === TourStatus::INACTIVE->value) {
            return;
        }

        $bookableLines = collect($this->tourRepository->getUpcomingBookingAvailabilityValues($tourId));

        if ($bookableLines->isEmpty()) {
            return;
        }

        $openValue = TourScheduleBookingAvailability::OPEN->value;
        $hasOpenSeat = $bookableLines->contains($openValue);

        $target = $hasOpenSeat
            ? TourBookingAvailability::OPEN->value
            : TourBookingAvailability::SOLD_OUT->value;

        $currentAvailability = $tour->booking_availability?->value
            ?? TourBookingAvailability::OPEN->value;

        if ($currentAvailability !== $target) {
            $this->tourRepository->updateBookingAvailability($tourId, $target);
        }
    }
}
