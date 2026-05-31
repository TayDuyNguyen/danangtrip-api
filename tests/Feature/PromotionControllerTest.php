<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Repositories\Interfaces\PromotionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
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

    /**
     * Test admin GET /admin/promotions.
     */
    public function test_admin_list_promotions(): void
    {
        $paginator = new LengthAwarePaginator([
            new Promotion([
                'id' => 1,
                'code' => 'SUMMER50',
                'name' => 'Summer Discount',
                'discount_type' => 'percent',
                'discount_value' => '15.00',
                'status' => 'active',
            ]),
        ], 1, 10);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('adminList')
            ->once()
            ->andReturn($paginator);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/admin/promotions');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.data.0.code', 'SUMMER50');
    }

    /**
     * Test admin GET /admin/promotions/{id}.
     */
    public function test_admin_get_promotion_detail(): void
    {
        $promo = new Promotion([
            'id' => 5,
            'code' => 'DANANG100',
            'name' => 'Da Nang trip discount',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'status' => 'active',
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($promo);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/admin/promotions/5');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.code', 'DANANG100')
            ->assertJsonPath('data.discount_value', '100000.00');
    }

    /**
     * Test admin POST /admin/promotions.
     */
    public function test_admin_create_promotion(): void
    {
        $payload = [
            'code' => 'newpromo',
            'name' => 'New Promo Code',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'status' => 'active',
        ];

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('findByCode')
            ->once()
            ->with('NEWPROMO')
            ->andReturn(null);

        $mockRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['code'] === 'NEWPROMO' && $data['name'] === 'New Promo Code';
            }))
            ->andReturn(new Promotion([
                'id' => 10,
                'code' => 'NEWPROMO',
                'name' => 'New Promo Code',
                'discount_type' => 'percent',
                'discount_value' => 10,
            ]));

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->postJson('/api/v1/admin/promotions', $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath('code', 201)
            ->assertJsonPath('data.code', 'NEWPROMO');
    }

    /**
     * Test admin PUT /admin/promotions/{id}.
     */
    public function test_admin_update_promotion(): void
    {
        $payload = [
            'code' => 'updatedpromo',
            'name' => 'Updated Promo Code',
        ];

        $oldPromo = new Promotion([
            'id' => 10,
            'code' => 'NEWPROMO',
            'name' => 'New Promo Code',
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('find')
            ->twice() // twice: once in service beginning, once after update to return
            ->with(10)
            ->andReturn($oldPromo);

        $mockRepo->shouldReceive('findByCode')
            ->once()
            ->with('UPDATEDPROMO')
            ->andReturn(null);

        $mockRepo->shouldReceive('update')
            ->once()
            ->with(10, Mockery::on(function ($data) {
                return $data['code'] === 'UPDATEDPROMO' && $data['name'] === 'Updated Promo Code';
            }))
            ->andReturn(true);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->putJson('/api/v1/admin/promotions/10', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Promotion updated successfully.');
    }

    /**
     * Test admin PATCH /admin/promotions/{id}/status.
     */
    public function test_admin_toggle_status(): void
    {
        $promo = new Promotion([
            'id' => 10,
            'code' => 'NEWPROMO',
            'status' => 'active',
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($promo);

        $mockRepo->shouldReceive('toggleStatus')
            ->once()
            ->with(10, 'inactive')
            ->andReturn(true);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->patchJson('/api/v1/admin/promotions/10/status', [
            'status' => 'inactive',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Promotion status updated successfully.');
    }

    /**
     * Test admin DELETE /admin/promotions/{id}.
     */
    public function test_admin_delete_promotion(): void
    {
        $promo = new Promotion([
            'id' => 10,
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('find')
            ->once()
            ->with(10)
            ->andReturn($promo);

        $mockRepo->shouldReceive('delete')
            ->once()
            ->with(10)
            ->andReturn(true);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->deleteJson('/api/v1/admin/promotions/10');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Promotion deleted successfully.');
    }

    /**
     * Test public GET /promotions.
     */
    public function test_public_get_active_promotions(): void
    {
        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('getActivePromotions')
            ->once()
            ->andReturn(new Collection([
                new Promotion([
                    'code' => 'SUMMER50',
                    'name' => 'Summer Discount',
                    'discount_type' => 'percent',
                    'discount_value' => '15.00',
                ]),
            ]));

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->getJson('/api/v1/promotions');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.0.code', 'SUMMER50');
    }

    /**
     * Test public POST /promotions/validate success.
     */
    public function test_public_validate_code_success(): void
    {
        $promo = new Promotion([
            'code' => 'SUMMER50',
            'name' => 'Summer Discount',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 500000,
            'max_discount_amount' => 100000,
            'status' => 'active',
            'used_count' => 5,
            'usage_limit' => 10,
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('findByCode')
            ->once()
            ->with('SUMMER50')
            ->andReturn($promo);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->postJson('/api/v1/promotions/validate', [
            'code' => 'SUMMER50',
            'order_total' => 600000,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.discount_amount', 60000) // 10% of 600000 = 60000
            ->assertJsonPath('data.final_amount', 540000);
    }

    /**
     * Test public POST /promotions/validate failure due to min order amount.
     */
    public function test_public_validate_code_fails_under_min_amount(): void
    {
        $promo = new Promotion([
            'code' => 'SUMMER50',
            'name' => 'Summer Discount',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 500000,
            'status' => 'active',
        ]);

        $mockRepo = Mockery::mock(PromotionRepositoryInterface::class);
        $mockRepo->shouldReceive('findByCode')
            ->once()
            ->with('SUMMER50')
            ->andReturn($promo);

        $this->app->instance(PromotionRepositoryInterface::class, $mockRepo);

        $response = $this->postJson('/api/v1/promotions/validate', [
            'code' => 'SUMMER50',
            'order_total' => 400000, // below 500000
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', 'Minimum order amount is 500000.00.');
    }
}
