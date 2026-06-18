<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpireUnpaidBookingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required for ExpireUnpaidBookings tests.');
        }

        $this->resetSchema();
    }

    public function test_expire_unpaid_bookings_cancels_releases_seats_and_notifies_user(): void
    {
        config([
            'booking.unpaid_expiry_minutes' => 60,
            'booking.payment_session_minutes' => 15,
        ]);

        $user = User::query()->create(['email' => 'guest@example.com']);
        $tour = Tour::query()->create([
            'name' => 'Ba Na Hills',
            'slug' => 'ba-na-hills',
            'status' => 'active',
        ]);
        $schedule = TourSchedule::query()->create([
            'tour_id' => $tour->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'max_people' => 20,
            'booked_people' => 2,
            'booking_availability' => 'open',
            'status' => 'available',
        ]);

        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'booking_code' => 'BOOK-EXPTEST',
            'customer_name' => 'Test User',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '0900000000',
            'total_amount' => 1000000,
            'discount_amount' => 0,
            'final_amount' => 1000000,
            'payment_method' => 'sepay',
            'payment_status' => 'pending',
            'booking_status' => BookingStatus::PENDING->value,
            'booked_at' => now()->subMinutes(61),
        ]);

        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'tour_id' => $tour->id,
            'tour_schedule_id' => $schedule->id,
            'item_type' => 'tour',
            'item_name' => $tour->name,
            'travel_date' => $schedule->start_date,
            'quantity_adult' => 2,
            'quantity_child' => 0,
            'quantity_infant' => 0,
            'unit_price_adult' => 500000,
            'unit_price_child' => 0,
            'unit_price_infant' => 0,
            'subtotal' => 1000000,
            'status' => 'active',
        ]);

        Payment::query()->create([
            'booking_id' => $booking->id,
            'transaction_code' => 'PAY-EXPTEST01',
            'amount' => 1000000,
            'payment_method' => 'sepay',
            'payment_status' => PaymentStatus::PENDING->value,
            'payment_gateway' => 'sepay',
        ]);

        $result = app(BookingService::class)->expireUnpaidBookings(Carbon::now(), 60);

        $this->assertSame(1, $result['expired']);
        $booking->refresh();
        $schedule->refresh();
        $payment = Payment::query()->where('booking_id', $booking->id)->first();

        $this->assertSame(BookingStatus::CANCELLED->value, $booking->booking_status);
        $this->assertSame(PaymentStatus::FAILED->value, $booking->payment_status);
        $this->assertStringContainsString('60 phút', (string) $booking->cancellation_reason);
        $this->assertSame(0, $schedule->booked_people);
        $this->assertSame(PaymentStatus::FAILED->value, $payment?->payment_status);
        $this->assertSame(
            1,
            Notification::query()
                ->where('user_id', $user->id)
                ->where('type', 'booking_unpaid_expired')
                ->count()
        );
    }

    public function test_expire_unpaid_bookings_skips_recent_pending_booking(): void
    {
        config(['booking.unpaid_expiry_minutes' => 60]);

        $user = User::query()->create(['email' => 'recent@example.com']);
        $tour = Tour::query()->create([
            'name' => 'Son Tra',
            'slug' => 'son-tra',
            'status' => 'active',
        ]);
        $schedule = TourSchedule::query()->create([
            'tour_id' => $tour->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'max_people' => 10,
            'booked_people' => 1,
            'booking_availability' => 'open',
            'status' => 'available',
        ]);

        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'booking_code' => 'BOOK-RECENT',
            'customer_name' => 'Recent User',
            'customer_email' => 'recent@example.com',
            'customer_phone' => '0900000001',
            'total_amount' => 500000,
            'discount_amount' => 0,
            'final_amount' => 500000,
            'payment_method' => 'sepay',
            'payment_status' => 'pending',
            'booking_status' => BookingStatus::PENDING->value,
            'booked_at' => now()->subMinutes(20),
        ]);

        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'tour_id' => $tour->id,
            'tour_schedule_id' => $schedule->id,
            'item_type' => 'tour',
            'item_name' => $tour->name,
            'travel_date' => $schedule->start_date,
            'quantity_adult' => 1,
            'quantity_child' => 0,
            'quantity_infant' => 0,
            'unit_price_adult' => 500000,
            'unit_price_child' => 0,
            'unit_price_infant' => 0,
            'subtotal' => 500000,
            'status' => 'active',
        ]);

        $result = app(BookingService::class)->expireUnpaidBookings(Carbon::now(), 60);

        $this->assertSame(0, $result['expired']);
        $booking->refresh();
        $schedule->refresh();

        $this->assertSame(BookingStatus::PENDING->value, $booking->booking_status);
        $this->assertSame(1, $schedule->booked_people);
        $this->assertSame(0, Notification::query()->where('type', 'booking_unpaid_expired')->count());
    }

    public function test_expire_unpaid_bookings_does_not_reopen_schedule_past_deadline(): void
    {
        $user = User::query()->create(['email' => 'deadline@example.com']);
        $tour = Tour::query()->create([
            'name' => 'Hoi An',
            'slug' => 'hoi-an',
            'status' => 'active',
        ]);
        $schedule = TourSchedule::query()->create([
            'tour_id' => $tour->id,
            'start_date' => now()->addDay()->toDateString(),
            'booking_deadline' => now()->subMinute(),
            'max_people' => 2,
            'booked_people' => 2,
            'booking_availability' => 'sold_out',
            'status' => 'available',
        ]);
        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'booking_code' => 'BOOK-DEADLINE',
            'customer_name' => 'Deadline User',
            'customer_email' => 'deadline@example.com',
            'customer_phone' => '0900000002',
            'total_amount' => 500000,
            'discount_amount' => 0,
            'final_amount' => 500000,
            'payment_method' => 'sepay',
            'payment_status' => 'pending',
            'booking_status' => BookingStatus::PENDING->value,
            'booked_at' => now()->subMinutes(61),
        ]);
        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'tour_id' => $tour->id,
            'tour_schedule_id' => $schedule->id,
            'item_type' => 'tour',
            'item_name' => $tour->name,
            'travel_date' => $schedule->start_date,
            'quantity_adult' => 2,
            'quantity_child' => 0,
            'quantity_infant' => 0,
            'unit_price_adult' => 250000,
            'unit_price_child' => 0,
            'unit_price_infant' => 0,
            'subtotal' => 500000,
            'status' => 'active',
        ]);

        app(BookingService::class)->expireUnpaidBookings(Carbon::now(), 60);

        $schedule->refresh();
        $this->assertSame(0, $schedule->booked_people);
        $this->assertSame('sold_out', $schedule->booking_availability->value);
    }

    public function test_preview_unpaid_bookings_does_not_mutate_booking(): void
    {
        $user = User::query()->create(['email' => 'preview@example.com']);
        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'booking_code' => 'BOOK-PREVIEW',
            'customer_name' => 'Preview User',
            'customer_email' => 'preview@example.com',
            'customer_phone' => '0900000003',
            'total_amount' => 500000,
            'discount_amount' => 0,
            'final_amount' => 500000,
            'payment_method' => 'sepay',
            'payment_status' => 'pending',
            'booking_status' => BookingStatus::PENDING->value,
            'booked_at' => now()->subMinutes(61),
        ]);

        $preview = app(BookingService::class)->previewUnpaidBookings(Carbon::now(), 60);

        $this->assertSame(1, $preview['count']);
        $this->assertSame([$booking->id], $preview['booking_ids']);
        $this->assertSame(BookingStatus::PENDING->value, $booking->fresh()->booking_status);
    }

    private function resetSchema(): void
    {
        foreach ([
            'notifications',
            'payments',
            'booking_items',
            'bookings',
            'tour_schedules',
            'tours',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id');
            $table->date('start_date');
            $table->integer('max_people')->default(20);
            $table->integer('booked_people')->default(0);
            $table->string('booking_availability')->default('open');
            $table->string('status')->default('available');
            $table->timestamp('booking_deadline')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->unsignedBigInteger('user_voucher_id')->nullable();
            $table->string('booking_code')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('booking_status')->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('tour_id');
            $table->unsignedBigInteger('tour_schedule_id');
            $table->string('item_type')->default('tour');
            $table->string('item_name');
            $table->date('travel_date');
            $table->integer('quantity_adult')->default(0);
            $table->integer('quantity_child')->default(0);
            $table->integer('quantity_infant')->default(0);
            $table->decimal('unit_price_adult', 12, 2)->default(0);
            $table->decimal('unit_price_child', 12, 2)->default(0);
            $table->decimal('unit_price_infant', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('transaction_code')->unique();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_gateway')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
}
