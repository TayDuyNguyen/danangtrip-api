<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\PointReward;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\UserPointBalance;
use App\Models\UserVoucher;
use App\Services\PointService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class PointServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for PointService database integration tests.');
        }

        $this->resetSchema();
    }

    public function test_award_points_creates_balance_transaction_and_notification_once_per_source(): void
    {
        $this->seedUser(1);
        PointRule::query()->create([
            'action_key' => 'booking_paid',
            'name' => 'Thanh toán đơn tour thành công',
            'points' => 10,
            'requires_approval' => false,
            'status' => 'active',
        ]);

        $service = new PointService;
        $first = $service->awardPoints(1, 'booking_paid', 'booking', 99, 'Thanh toán BOOK-TEST');
        $second = $service->awardPoints(1, 'booking_paid', 'booking', 99, 'Thanh toán BOOK-TEST');

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(10, UserPointBalance::query()->where('user_id', 1)->value('available_points'));
        $this->assertSame(1, PointTransaction::query()->where('user_id', 1)->count());
        $this->assertSame(1, Notification::query()->where('user_id', 1)->where('type', 'point_earned')->count());
    }

    public function test_award_points_respects_daily_limit_per_action_key(): void
    {
        $this->seedUser(1);
        PointRule::query()->create([
            'action_key' => 'review_quality',
            'name' => 'Đánh giá chất lượng',
            'points' => 5,
            'max_per_day' => 1,
            'requires_approval' => true,
            'status' => 'active',
        ]);

        $service = new PointService;
        $first = $service->awardPoints(1, 'review_quality', 'rating', 1, 'Review 1', true);
        $second = $service->awardPoints(1, 'review_quality', 'rating', 2, 'Review 2', true);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(5, UserPointBalance::query()->where('user_id', 1)->value('available_points'));
        $this->assertSame(1, PointTransaction::query()->where('action_key', 'review_quality')->count());
    }

    public function test_redeem_reward_spends_points_creates_voucher_and_notification(): void
    {
        $this->seedUser(1);
        UserPointBalance::query()->create([
            'user_id' => 1,
            'available_points' => 100,
            'lifetime_earned' => 100,
            'lifetime_spent' => 0,
        ]);
        $reward = $this->seedReward(['required_points' => 80, 'usage_limit_per_user' => 1]);

        $result = (new PointService)->redeemReward(1, $reward->id);

        $this->assertSame(20, $result['balance']->available_points);
        $this->assertSame(1, UserVoucher::query()->where('user_id', 1)->where('point_reward_id', $reward->id)->count());
        $this->assertSame(1, PointTransaction::query()->where('type', 'spend')->where('action_key', 'redeem_reward')->count());
        $this->assertSame(1, Notification::query()->where('type', 'point_voucher_redeemed')->count());
    }

    public function test_redeem_reward_blocks_usage_limit_per_user(): void
    {
        $this->seedUser(1);
        UserPointBalance::query()->create([
            'user_id' => 1,
            'available_points' => 500,
            'lifetime_earned' => 500,
            'lifetime_spent' => 0,
        ]);
        $reward = $this->seedReward(['required_points' => 50, 'usage_limit_per_user' => 1]);
        UserVoucher::query()->create([
            'user_id' => 1,
            'point_reward_id' => $reward->id,
            'code' => 'DTV-USED-001',
            'name' => $reward->name,
            'discount_type' => 'fixed',
            'discount_value' => 1000,
            'min_order_amount' => 1000,
            'expires_at' => now()->addDay(),
            'status' => 'used',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bạn đã đổi tối đa số lần cho phần thưởng này.');

        (new PointService)->redeemReward(1, $reward->id);
    }

    private function resetSchema(): void
    {
        foreach (['notifications', 'user_vouchers', 'point_transactions', 'point_rewards', 'point_rules', 'user_point_balances', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('user_point_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('available_points')->default(0);
            $table->unsignedInteger('lifetime_earned')->default(0);
            $table->unsignedInteger('lifetime_spent')->default(0);
            $table->timestamps();
        });

        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('action_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points');
            $table->unsignedInteger('max_per_day')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('point_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('required_points');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->unsignedInteger('expires_in_days')->default(30);
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('action_key')->nullable();
            $table->integer('points');
            $table->unsignedInteger('balance_after');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->string('status')->default('approved');
            $table->timestamps();
        });

        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('point_reward_id')->nullable();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 30);
            $table->string('title');
            $table->text('content');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function seedUser(int $id): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'email' => "user{$id}@example.test",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedReward(array $overrides = []): PointReward
    {
        return PointReward::query()->create(array_merge([
            'code' => 'PTEST',
            'name' => 'Voucher test',
            'required_points' => 50,
            'discount_type' => 'fixed',
            'discount_value' => 1000,
            'min_order_amount' => 1000,
            'expires_in_days' => 7,
            'usage_limit_per_user' => 1,
            'status' => 'active',
        ], $overrides));
    }
}
