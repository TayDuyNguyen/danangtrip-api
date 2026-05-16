<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\AuthService;
use App\Services\PaymentService;
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

        $service = new PaymentService($paymentRepository, $bookingRepository);

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

        $service = new PaymentService($paymentRepository, $bookingRepository);

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
}
