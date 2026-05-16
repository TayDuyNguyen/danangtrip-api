<?php

namespace Tests\Feature;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Requests\Category\ShowCategoryRequest;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class AdminCategoryApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_categories_index_supports_q_filter_and_returns_paginated_payload(): void
    {
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('getCategories')
            ->once()
            ->andReturn(new LengthAwarePaginator(
                [[
                    'id' => 1,
                    'name' => 'Am thuc',
                    'slug' => 'am-thuc',
                    'icon_background' => '#E0F2FE',
                    'status' => 'active',
                    'sort_order' => 1,
                    'locations_count' => 0,
                ]],
                1,
                10,
                1
            ));
        $service = new CategoryService($repository);
        $this->app->instance(CategoryService::class, $service);

        $response = $this->getJson('/api/v1/admin/categories?q=am&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.per_page', 10)
            ->assertJsonPath('data.data.0.name', 'Am thuc')
            ->assertJsonPath('data.data.0.slug', 'am-thuc')
            ->assertJsonPath('data.data.0.icon_background', '#E0F2FE')
            ->assertJsonPath('data.data.0.locations_count', 0);
    }

    public function test_admin_categories_show_returns_category_detail_payload(): void
    {
        $category = new Category([
            'name' => 'Tham quan',
            'slug' => 'tham-quan',
            'icon_background' => '#CFFAFE',
            'status' => 'active',
            'sort_order' => 3,
        ]);
        $category->setAttribute('id', 15);
        $category->setAttribute('locations_count', 0);
        $category->setRelation('subcategories', collect());

        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('getAdminCategoryById')
            ->once()
            ->with(15)
            ->andReturn($category);
        $service = new CategoryService($repository);
        $controller = new CategoryController($service);
        $request = ShowCategoryRequest::create('/api/v1/admin/categories/15', 'GET');

        $response = $controller->show($request, 15);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(200, $payload['code']);
        $this->assertSame(15, $payload['data']['id']);
        $this->assertSame('Tham quan', $payload['data']['name']);
        $this->assertSame('tham-quan', $payload['data']['slug']);
        $this->assertSame('#CFFAFE', $payload['data']['icon_background']);
        $this->assertSame(0, $payload['data']['locations_count']);
    }

    public function test_admin_categories_update_status_returns_updated_category_object(): void
    {
        $category = new Category([
            'name' => 'Giai tri',
            'slug' => 'giai-tri',
            'icon_background' => '#FCE7F3',
            'status' => 'inactive',
            'sort_order' => 4,
        ]);
        $category->setAttribute('id', 7);

        $updatedCategory = new Category([
            'name' => 'Giai tri',
            'slug' => 'giai-tri',
            'icon_background' => '#FCE7F3',
            'status' => 'active',
            'sort_order' => 4,
        ]);
        $updatedCategory->setAttribute('id', 7);

        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(7)
            ->andReturn($category);
        $repository->shouldReceive('updateStatus')
            ->once()
            ->with(7, 'active')
            ->andReturn(true);
        $repository->shouldReceive('getAdminCategoryById')
            ->once()
            ->with(7)
            ->andReturn($updatedCategory);

        $service = new CategoryService($repository);
        $this->app->instance(CategoryService::class, $service);

        $response = $this->patchJson('/api/v1/admin/categories/7/status', [
            'status' => 'active',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Category status updated successfully')
            ->assertJsonPath('data.id', 7)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_admin_categories_reorder_returns_success_message(): void
    {
        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('reorder')
            ->once()
            ->with([
                ['id' => 2, 'sort_order' => 1],
                ['id' => 1, 'sort_order' => 2],
            ])
            ->andReturn(true);

        $service = new CategoryService($repository);
        $controller = new CategoryController($service);
        $request = Mockery::mock('App\Http\Requests\Category\ReorderCategoryRequest');
        $request->shouldReceive('validated')
            ->once()
            ->with('items')
            ->andReturn([
                ['id' => 2, 'sort_order' => 1],
                ['id' => 1, 'sort_order' => 2],
            ]);

        $response = $controller->reorder($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(HttpStatusCode::SUCCESS->value, $payload['code']);
        $this->assertSame('Categories reordered successfully', $payload['message']);
    }

    public function test_delete_category_returns_conflict_when_locations_exist(): void
    {
        $category = new Category([
            'name' => 'Category with locations',
            'slug' => 'category-with-locations',
            'status' => 'active',
        ]);
        $category->setAttribute('id', 12);

        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(12)
            ->andReturn($category);
        $repository->shouldReceive('hasSubcategories')
            ->once()
            ->with(12)
            ->andReturn(false);
        $repository->shouldReceive('hasLocations')
            ->once()
            ->with(12)
            ->andReturn(true);

        $service = new CategoryService($repository);

        $result = $service->deleteCategory(12);

        $this->assertSame(HttpStatusCode::CONFLICT->value, $result['status']);
        $this->assertSame('Cannot delete category because it has locations', $result['message']);
    }
}
