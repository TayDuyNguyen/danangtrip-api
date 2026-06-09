<?php

namespace App\Console\Commands;

use App\Services\TourScheduleAvailabilityService;
use App\Services\TourStatusSyncService;
use Illuminate\Console\Command;

final class SyncTourScheduleAvailability extends Command
{
    protected $signature = 'tour-schedules:sync-availability';

    protected $description = 'Close tour schedules that are past their deadline, full, cancelled, or already departed';

    public function handle(
        TourScheduleAvailabilityService $availabilityService,
        TourStatusSyncService $tourStatusSyncService
    ): int {
        $result = $availabilityService->closeUnavailable();

        foreach ($result['tour_ids'] as $tourId) {
            $tourStatusSyncService->syncByTourId($tourId);
        }

        $this->info("Closed {$result['closed']} tour schedule(s).");

        return self::SUCCESS;
    }
}
