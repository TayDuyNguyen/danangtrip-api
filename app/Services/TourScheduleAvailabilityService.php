<?php

namespace App\Services;

use App\Enums\TourScheduleBookingAvailability;
use App\Models\TourSchedule;
use Illuminate\Support\Carbon;

class TourScheduleAvailabilityService
{
    /**
     * Close schedules that can no longer accept bookings.
     *
     * @return array{closed: int, tour_ids: int[]}
     */
    public function closeUnavailable(?Carbon $now = null): array
    {
        $now ??= now();

        $schedules = TourSchedule::query()
            ->where('booking_availability', TourScheduleBookingAvailability::OPEN->value)
            ->where(function ($query) use ($now): void {
                $query->whereDate('start_date', '<', $now->toDateString())
                    ->orWhere('booking_deadline', '<=', $now)
                    ->orWhereColumn('booked_people', '>=', 'max_people')
                    ->orWhere('status', 'cancelled');
            })
            ->get(['id', 'tour_id']);

        if ($schedules->isEmpty()) {
            return [
                'closed' => 0,
                'tour_ids' => [],
            ];
        }

        $closed = TourSchedule::query()
            ->whereKey($schedules->pluck('id'))
            ->where('booking_availability', TourScheduleBookingAvailability::OPEN->value)
            ->update([
                'booking_availability' => TourScheduleBookingAvailability::SOLD_OUT->value,
                'updated_at' => $now,
            ]);

        return [
            'closed' => $closed,
            'tour_ids' => $schedules->pluck('tour_id')->unique()->values()->all(),
        ];
    }
}
