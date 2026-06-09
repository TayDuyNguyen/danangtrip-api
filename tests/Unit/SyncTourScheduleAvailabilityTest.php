<?php

namespace Tests\Unit;

use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Services\TourScheduleAvailabilityService;
use App\Services\TourStatusSyncService;
use Mockery;
use Tests\TestCase;

class SyncTourScheduleAvailabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_command_closes_schedules_and_syncs_affected_tours(): void
    {
        $availabilityService = Mockery::mock(TourScheduleAvailabilityService::class);
        $availabilityService->shouldReceive('closeUnavailable')
            ->once()
            ->andReturn([
                'closed' => 14,
                'tour_ids' => [10, 20],
            ]);

        $tourRepository = Mockery::mock(TourRepositoryInterface::class);
        $tourRepository->shouldReceive('find')->once()->with(10)->andReturnNull();
        $tourRepository->shouldReceive('find')->once()->with(20)->andReturnNull();

        $this->app->instance(TourScheduleAvailabilityService::class, $availabilityService);
        $this->app->instance(TourStatusSyncService::class, new TourStatusSyncService($tourRepository));

        $this->artisan('tour-schedules:sync-availability')
            ->expectsOutput('Closed 14 tour schedule(s).')
            ->assertSuccessful();
    }
}
