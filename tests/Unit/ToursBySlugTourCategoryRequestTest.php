<?php

namespace Tests\Unit;

use App\Http\Requests\TourCategory\ToursBySlugTourCategoryRequest;
use Illuminate\Routing\Route;
use Mockery;
use Tests\TestCase;

class ToursBySlugTourCategoryRequestTest extends TestCase
{
    public function test_prepare_for_validation_merges_slug_from_route(): void
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('parameter')
            ->once()
            ->with('slug', null)
            ->andReturn('tour-ba-na-hills');

        $request = new class extends ToursBySlugTourCategoryRequest
        {
            public function prepare(): void
            {
                $this->prepareForValidation();
            }
        };
        $request->setRouteResolver(fn () => $route);

        $request->prepare();

        $this->assertSame('tour-ba-na-hills', $request->input('slug'));
    }
}
