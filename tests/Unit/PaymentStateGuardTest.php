<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Services\BookingPaymentNotificationService;
use App\Services\PaymentService;
use App\Services\PointService;
use App\Services\RefundService;
use App\Services\SepayPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class PaymentStateGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_payment_rejects_cancelled_booking_before_creating_payment(): void
    {
        $booking = new Booking([
            'id' => 10,
            'user_id' => 5,
            'booking_code' => 'BOOK-CANCELLED',
            'booking_status' => BookingStatus::CANCELLED->value,
            'payment_status' => PaymentStatus::FAILED->value,
            'final_amount' => 500000,
        ]);
        $booking->id = 10;
        $booking->user_id = 5;

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);
        $bookingRepository->shouldReceive('findForUpdate')
            ->once()
            ->with(10)
            ->andReturn($booking);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldNotReceive('create');
        $paymentRepository->shouldNotReceive('markExpiredPendingPaymentsFailedByBookingId');

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());
        Auth::shouldReceive('id')->once()->andReturn(5);

        $result = $this->makeService($bookingRepository, $paymentRepository)->createPayment([
            'booking_id' => 10,
            'payment_method' => 'sepay',
        ]);

        $this->assertSame(HttpStatusCode::BAD_REQUEST->value, $result['status']);
        $this->assertSame('This booking can no longer accept payments', $result['message']);
    }

    public function test_retry_payment_rejects_completed_booking(): void
    {
        $booking = new Booking([
            'id' => 11,
            'user_id' => 5,
            'booking_code' => 'BOOK-COMPLETED',
            'booking_status' => BookingStatus::COMPLETED->value,
            'payment_status' => PaymentStatus::FAILED->value,
        ]);
        $booking->id = 11;
        $booking->user_id = 5;

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);
        $bookingRepository->shouldReceive('findByCode')
            ->once()
            ->with('BOOK-COMPLETED')
            ->andReturn($booking);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldNotReceive('create');
        Auth::shouldReceive('id')->once()->andReturn(5);

        $result = $this->makeService($bookingRepository, $paymentRepository)
            ->retryPayment('BOOK-COMPLETED');

        $this->assertSame(HttpStatusCode::BAD_REQUEST->value, $result['status']);
        $this->assertSame('This booking can no longer accept payments', $result['message']);
    }

    private function makeService(
        BookingRepositoryInterface $bookingRepository,
        PaymentRepositoryInterface $paymentRepository
    ): PaymentService {
        return new PaymentService(
            $paymentRepository,
            $bookingRepository,
            Mockery::mock(SepayPaymentService::class),
            Mockery::mock(BookingPaymentNotificationService::class),
            new PointService,
            new RefundService(new PointService),
        );
    }
}
