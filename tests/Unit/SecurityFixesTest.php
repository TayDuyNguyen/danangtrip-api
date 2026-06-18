<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\AuthService;
use App\Services\BookingPaymentNotificationService;
use App\Services\PaymentService;
use App\Services\PointService;
use App\Services\RefundService;
use App\Services\SepayPaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Mockery;
use Tests\TestCase;

class SecurityFixesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_payment_callback_rejects_unsigned_gateway(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(static fn ($callback) => $callback());

        $payment = new Payment([
            'transaction_code' => 'PAY-TEST123',
            'payment_status' => PaymentStatus::PENDING->value,
            'payment_gateway' => 'cash',
        ]);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findByTransactionCodeForUpdate')
            ->once()
            ->with('PAY-TEST123')
            ->andReturn($payment);

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);

        $service = $this->makePaymentService($paymentRepository, $bookingRepository);

        $result = $service->handleCallback([
            'transaction_code' => 'PAY-TEST123',
            'status' => 'success',
        ]);

        $this->assertSame(HttpStatusCode::BAD_REQUEST->value, $result['status']);
        $this->assertSame('Unsupported payment gateway callback', $result['message']);
    }

    public function test_payment_status_requires_booking_owner(): void
    {
        Auth::shouldReceive('id')->once()->andReturn(10);

        $booking = new Booking(['user_id' => 99]);
        $payment = new Payment([
            'transaction_code' => 'PAY-STATUS1',
            'payment_status' => PaymentStatus::PENDING->value,
            'booking_id' => 55,
        ]);
        $payment->setRelation('booking', $booking);

        $paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $paymentRepository->shouldReceive('findByTransactionCode')
            ->once()
            ->with('PAY-STATUS1')
            ->andReturn($payment);

        $bookingRepository = Mockery::mock(BookingRepositoryInterface::class);

        $service = $this->makePaymentService($paymentRepository, $bookingRepository);

        $result = $service->getStatus('PAY-STATUS1');

        $this->assertSame(HttpStatusCode::FORBIDDEN->value, $result['status']);
        $this->assertSame('You do not have permission to view this payment', $result['message']);
    }

    public function test_reset_password_updates_password_and_revokes_refresh_tokens(): void
    {
        $userRepository = Mockery::mock(UserRepositoryInterface::class);
        $refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);

        Password::shouldReceive('broker')->once()->andReturnSelf();
        Password::shouldReceive('reset')
            ->once()
            ->andReturnUsing(function (array $credentials, callable $callback) {
                $this->assertSame('user@example.com', $credentials['email']);
                $this->assertSame('reset-token', $credentials['token']);
                $this->assertSame('new-password-123', $credentials['password']);
                $this->assertSame('new-password-123', $credentials['password_confirmation']);
                $this->assertIsCallable($callback);

                return Password::PASSWORD_RESET;
            });

        $service = new AuthService($userRepository, $refreshTokenRepository);

        $result = $service->resetPassword([
            'email' => 'user@example.com',
            'token' => 'reset-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $result['status']);
        $this->assertSame('Password has been reset successfully.', $result['message']);
    }

    public function test_refund_admin_payload_masks_account_for_list_view(): void
    {
        $refund = Mockery::mock(RefundRequest::class)->makePartial();
        $refund->shouldReceive('loadMissing')->with('booking')->andReturnSelf();

        $refund->id = 1;
        $refund->refund_code = 'RF-TEST12345';
        $refund->booking_id = 10;
        $refund->reason_type = 'cancellation';
        $refund->status = 'pending';
        $refund->requested_amount = 100000;
        $refund->approved_amount = 100000;
        $refund->refund_percent = 100;
        $refund->bank_code = 'VCB';
        $refund->account_no = '1234567890';
        $refund->account_name = 'NGUYEN VAN A';
        $refund->reason = 'Reason';
        $refund->policy_snapshot = null;
        $refund->transfer_reference = null;
        $refund->requested_at = null;
        $refund->completed_at = null;

        $booking = Mockery::mock(Booking::class)->makePartial();
        $booking->booking_code = 'BOOK-1234';
        $refund->booking = $booking;

        $refundService = new RefundService(new PointService);
        $payload = $refundService->adminPayload($refund, true);

        $this->assertSame('******7890', $payload['masked_account_no']);
        $this->assertArrayNotHasKey('account_no', $payload);
        $this->assertArrayNotHasKey('qr_image_url', $payload);
    }

    public function test_refund_admin_payload_reveals_account_and_generates_vietqr_for_detail_view(): void
    {
        $refund = Mockery::mock(RefundRequest::class)->makePartial();
        $refund->shouldReceive('loadMissing')->with('booking')->andReturnSelf();

        $refund->id = 1;
        $refund->refund_code = 'RF-TEST12345';
        $refund->booking_id = 10;
        $refund->reason_type = 'cancellation';
        $refund->status = 'pending';
        $refund->requested_amount = 100000;
        $refund->approved_amount = 100000;
        $refund->refund_percent = 100;
        $refund->bank_code = 'VCB';
        $refund->account_no = '1234567890';
        $refund->account_name = 'NGUYEN VAN A';
        $refund->reason = 'Reason';
        $refund->policy_snapshot = null;
        $refund->transfer_reference = null;
        $refund->requested_at = null;
        $refund->completed_at = null;

        $booking = Mockery::mock(Booking::class)->makePartial();
        $booking->booking_code = 'BOOK-1234';
        $refund->booking = $booking;

        $refundService = new RefundService(new PointService);
        $payload = $refundService->adminPayload($refund, false);

        $this->assertSame('******7890', $payload['masked_account_no']);
        $this->assertSame('1234567890', $payload['account_no']);
        $this->assertStringContainsString('img.vietqr.io/image/VCB-1234567890-compact2.png', $payload['qr_image_url']);
        $this->assertStringContainsString('amount=100000', $payload['qr_image_url']);
        $this->assertStringContainsString('addInfo=RF-TEST12345', $payload['qr_image_url']);
        $this->assertStringContainsString('accountName=NGUYEN%20VAN%20A', $payload['qr_image_url']);
    }

    private function makePaymentService(
        PaymentRepositoryInterface $paymentRepository,
        BookingRepositoryInterface $bookingRepository
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
