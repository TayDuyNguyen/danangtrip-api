<?php

namespace Tests\Feature;

use App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use App\Repositories\Interfaces\BlogPostRepositoryInterface;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\SearchLogRepositoryInterface;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use App\Repositories\Interfaces\TourCategoryRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        Cache::forget('public_homepage_data_v2');
    }

    protected function tearDown(): void
    {
        Cache::forget('public_homepage_data_v2');
        parent::tearDown();
    }

    /**
     * Helper to mock all backing repositories with successful empty collections/paginators.
     */
    protected function mockAllRepositoriesWithSuccess(): void
    {
        $mockUser = \Mockery::mock(UserRepositoryInterface::class);
        $mockUser->shouldReceive('count')->andReturn(10);
        $this->app->instance(UserRepositoryInterface::class, $mockUser);

        $mockLocation = \Mockery::mock(LocationRepositoryInterface::class);
        $mockLocation->shouldReceive('count')->andReturn(5);
        $mockLocation->shouldReceive('getTotalViewCount')->andReturn(100);
        $mockLocation->shouldReceive('getFeaturedLocations')->andReturn(new Collection);
        $this->app->instance(LocationRepositoryInterface::class, $mockLocation);

        $mockTour = \Mockery::mock(TourRepositoryInterface::class);
        $mockTour->shouldReceive('count')->andReturn(3);
        $mockTour->shouldReceive('getFeaturedTours')->andReturn(new Collection);
        $mockTour->shouldReceive('getHotTours')->andReturn(new Collection);
        $this->app->instance(TourRepositoryInterface::class, $mockTour);

        $mockRating = \Mockery::mock(RatingRepositoryInterface::class);
        $mockRating->shouldReceive('count')->andReturn(12);
        $this->app->instance(RatingRepositoryInterface::class, $mockRating);

        $mockBlogPost = \Mockery::mock(BlogPostRepositoryInterface::class);
        $mockBlogPost->shouldReceive('count')->andReturn(4);
        $paginator = new LengthAwarePaginator([], 0, 3);
        $mockBlogPost->shouldReceive('getPublicPosts')->andReturn($paginator);
        $this->app->instance(BlogPostRepositoryInterface::class, $mockBlogPost);

        $mockBooking = \Mockery::mock(BookingRepositoryInterface::class);
        $this->app->instance(BookingRepositoryInterface::class, $mockBooking);

        $mockPayment = \Mockery::mock(PaymentRepositoryInterface::class);
        $this->app->instance(PaymentRepositoryInterface::class, $mockPayment);

        $mockSearchLog = \Mockery::mock(SearchLogRepositoryInterface::class);
        $this->app->instance(SearchLogRepositoryInterface::class, $mockSearchLog);

        $mockCategory = \Mockery::mock(CategoryRepositoryInterface::class);
        $mockCategory->shouldReceive('getPublicCategories')->andReturn(new Collection);
        $this->app->instance(CategoryRepositoryInterface::class, $mockCategory);

        $mockTourCategory = \Mockery::mock(TourCategoryRepositoryInterface::class);
        $mockTourCategory->shouldReceive('getActiveCategories')->andReturn(new Collection);
        $this->app->instance(TourCategoryRepositoryInterface::class, $mockTourCategory);

        $mockBlogCategory = \Mockery::mock(BlogCategoryRepositoryInterface::class);
        $this->app->instance(BlogCategoryRepositoryInterface::class, $mockBlogCategory);

        $mockSetting = \Mockery::mock(SettingRepositoryInterface::class);
        $mockSetting->shouldReceive('getPublicSettings')->andReturn(new Collection);
        $this->app->instance(SettingRepositoryInterface::class, $mockSetting);
    }

    /**
     * Test GET /api/v1/home returns all consolidated homepage sections.
     */
    public function test_home_endpoint_returns_consolidated_public_sections(): void
    {
        $this->mockAllRepositoriesWithSuccess();

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'statistics',
                    'categories',
                    'featured_locations',
                    'tour_categories',
                    'featured_tours',
                    'hot_tours',
                    'latest_blogs',
                    'config',
                ],
            ]);
    }

    /**
     * Test that GET /api/v1/home caches its payload correctly.
     */
    public function test_home_endpoint_caches_payload(): void
    {
        $this->mockAllRepositoriesWithSuccess();

        $this->assertFalse(Cache::has('public_homepage_data_v2'));

        // First request should populate cache
        $this->getJson('/api/v1/home')->assertOk();

        $this->assertTrue(Cache::has('public_homepage_data_v2'));

        $cachedData = Cache::get('public_homepage_data_v2');
        $this->assertArrayHasKey('statistics', $cachedData);
        $this->assertArrayHasKey('categories', $cachedData);
        $this->assertArrayHasKey('featured_locations', $cachedData);
    }

    /**
     * Test that GET /api/v1/home does not cache on service failure.
     */
    public function test_home_endpoint_does_not_cache_on_service_failure(): void
    {
        $this->mockAllRepositoriesWithSuccess();

        // Override SettingRepository to fail (throw exception)
        $mockSettingRepo = \Mockery::mock(SettingRepositoryInterface::class);
        $mockSettingRepo->shouldReceive('getPublicSettings')
            ->once()
            ->andThrow(new \Exception('Mock database error'));

        $this->app->instance(SettingRepositoryInterface::class, $mockSettingRepo);

        $this->assertFalse(Cache::has('public_homepage_data_v2'));

        // Firing request should bypass cache due to setting failure (status is 500 inside config result)
        $response = $this->getJson('/api/v1/home');
        $response->assertOk();

        $this->assertFalse(Cache::has('public_homepage_data_v2'));
    }
}
