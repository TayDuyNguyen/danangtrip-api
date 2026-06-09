<?php

namespace Tests\Unit;

use App\Models\Rating;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\RatingImageRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Services\RatingService;
use Mockery;
use Tests\TestCase;

class RatingReadTrackingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_mark_viewed_only_updates_admin_read_state(): void
    {
        $newRating = new Rating([
            'status' => 'approved',
            'is_new' => true,
        ]);
        $newRating->setAttribute('id', 42);

        $viewedRating = new Rating([
            'status' => 'approved',
            'is_new' => false,
        ]);
        $viewedRating->setAttribute('id', 42);

        $ratingRepository = Mockery::mock(RatingRepositoryInterface::class);
        $ratingRepository->shouldReceive('find')->with(42)->once()->andReturn($newRating);
        $ratingRepository->shouldReceive('update')->with(42, ['is_new' => false])->once()->andReturnTrue();
        $ratingRepository->shouldReceive('find')->with(42)->once()->andReturn($viewedRating);

        $service = $this->makeService($ratingRepository);
        $result = $service->markViewed(42);

        $this->assertSame(200, $result['status']);
        $this->assertFalse($result['data']->is_new);
        $this->assertSame('approved', $result['data']->status);
    }

    public function test_mark_viewed_is_idempotent_for_an_already_viewed_rating(): void
    {
        $viewedRating = new Rating([
            'status' => 'approved',
            'is_new' => false,
        ]);
        $viewedRating->setAttribute('id', 42);

        $ratingRepository = Mockery::mock(RatingRepositoryInterface::class);
        $ratingRepository->shouldReceive('find')->with(42)->once()->andReturn($viewedRating);
        $ratingRepository->shouldNotReceive('update');

        $service = $this->makeService($ratingRepository);
        $result = $service->markViewed(42);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Already viewed', $result['message']);
    }

    private function makeService(RatingRepositoryInterface $ratingRepository): RatingService
    {
        return new RatingService(
            $ratingRepository,
            Mockery::mock(RatingImageRepositoryInterface::class),
            Mockery::mock(NotificationRepositoryInterface::class),
            Mockery::mock(LocationRepositoryInterface::class),
            Mockery::mock(TourRepositoryInterface::class),
        );
    }
}
