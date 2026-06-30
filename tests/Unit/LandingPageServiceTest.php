<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LandingPageRepositoryInterface;
use App\Services\LandingPageService;
use Mockery;
use Tests\TestCase;

class LandingPageServiceTest extends TestCase
{
    public function test_public_show_returns_published_landing_page(): void
    {
        $landingPage = (object) [
            'id' => 1,
            'slug' => 'du-lich-da-nang',
            'status' => 'published',
        ];

        $repository = Mockery::mock(LandingPageRepositoryInterface::class);
        $repository->shouldReceive('findPublishedBySlug')
            ->once()
            ->with('du-lich-da-nang')
            ->andReturn($landingPage);

        $result = (new LandingPageService($repository))->publicShow('du-lich-da-nang');

        $this->assertSame(HttpStatusCode::SUCCESS->value, $result['status']);
        $this->assertSame($landingPage, $result['data']);
    }

    public function test_public_show_returns_not_found_for_unpublished_slug(): void
    {
        $repository = Mockery::mock(LandingPageRepositoryInterface::class);
        $repository->shouldReceive('findPublishedBySlug')
            ->once()
            ->with('ban-nhap')
            ->andReturnNull();

        $result = (new LandingPageService($repository))->publicShow('ban-nhap');

        $this->assertSame(HttpStatusCode::NOT_FOUND->value, $result['status']);
    }
}
