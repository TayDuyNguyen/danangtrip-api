<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid
        {--minutes= : Override unpaid hold timeout in minutes}
        {--dry-run : Only list bookings that would be expired}';

    protected $description = 'Cancel pending bookings that were not paid within the hold window and release seats.';

    public function handle(BookingService $bookingService): int
    {
        $minutes = $this->option('minutes') !== null
            ? max(1, (int) $this->option('minutes'))
            : max(1, (int) config('booking.unpaid_expiry_minutes', 60));

        if ($this->option('dry-run')) {
            $preview = $bookingService->previewUnpaidBookings(Carbon::now(), $minutes);
            $this->info(sprintf(
                'Dry run only. Eligible unpaid bookings older than %d minute(s): %d.',
                $minutes,
                $preview['count']
            ));
            if ($preview['booking_ids'] !== []) {
                $this->line('Booking IDs: '.implode(', ', $preview['booking_ids']));
            }

            return self::SUCCESS;
        }

        $result = $bookingService->expireUnpaidBookings(Carbon::now(), $minutes);

        $this->info(sprintf(
            'Unpaid booking expiry finished. Expired: %d. Skipped: %d.',
            $result['expired'],
            $result['skipped']
        ));

        if ($result['booking_ids'] !== []) {
            $this->line('Booking IDs: '.implode(', ', $result['booking_ids']));
        }

        return self::SUCCESS;
    }
}
