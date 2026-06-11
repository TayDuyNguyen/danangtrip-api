<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Services\BookingPaymentNotificationService;
use App\Services\PointService;
use App\Services\SepayPaymentService;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class SepayPaymentAmountTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_checkout_rejects_negative_amount_instead_of_converting_it_to_positive(): void
    {
        $service = $this->makeService();
        $payment = new Payment([
            'amount' => -2000,
            'transaction_code' => 'PAY-NEGATIVE',
        ]);
        $booking = new Booking([
            'booking_code' => 'BOOK-NEGATIVE',
            'final_amount' => -2000,
            'total_amount' => 2000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VietQR amount must be greater than zero.');

        $service->buildCheckoutPayload($payment, $booking);
    }

    public function test_checkout_rejects_zero_amount_instead_of_falling_back_to_total_amount(): void
    {
        $service = $this->makeService();
        $payment = new Payment([
            'amount' => 0,
            'transaction_code' => 'PAY-FREE',
        ]);
        $booking = new Booking([
            'booking_code' => 'BOOK-FREE',
            'final_amount' => 0,
            'total_amount' => 2000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('VietQR amount must be greater than zero.');

        $service->buildCheckoutPayload($payment, $booking);
    }

    private function makeService(): SepayPaymentService
    {
        return new SepayPaymentService(
            Mockery::mock(PaymentRepositoryInterface::class),
            Mockery::mock(BookingRepositoryInterface::class),
            Mockery::mock(BookingPaymentNotificationService::class),
            new PointService,
        );
    }
}
