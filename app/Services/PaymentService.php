<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentStatus;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class PaymentService
 * (Xử lý logic nghiệp vụ liên quan đến thanh toán)
 */
class PaymentService
{
    /**
     * PaymentService constructor.
     * (Hàm khởi tạo)
     */
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Create payment link.
     * (Tạo link thanh toán)
     */
    public function createPayment(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $booking = $this->bookingRepository->find($data['booking_id']);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found',
                    ];
                }

                if ($booking->payment_status === PaymentStatus::PAID->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'This booking has already been paid',
                    ];
                }

                $transactionCode = 'PAY-'.strtoupper(Str::random(10));

                $payment = $this->paymentRepository->create([
                    'booking_id' => $booking->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $booking->total_amount,
                    'payment_method' => $data['payment_method'],
                    'payment_status' => PaymentStatus::PENDING->value,
                    'payment_gateway' => $data['payment_method'], // Mock gateway
                ]);

                // Mock payment link creation
                $paymentLink = "https://mock-gateway.com/pay/{$transactionCode}?method={$data['payment_method']}&amount={$booking->total_amount}";

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'payment' => $payment,
                        'payment_url' => $paymentLink,
                    ],
                    'message' => 'Payment link created successfully',
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create payment link',
            ];
        }
    }

    /**
     * Handle payment callback from gateway.
     * (Xử lý phản hồi từ cổng thanh toán)
     */
    public function handleCallback(array $gatewayData): array
    {
        try {
            return DB::transaction(function () use ($gatewayData) {
                $transactionCode = $gatewayData['transaction_code'] ?? null;
                $status = $gatewayData['status'] ?? 'failed';

                if (! $transactionCode) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Invalid transaction code',
                    ];
                }

                $payment = $this->paymentRepository->findByTransactionCodeForUpdate($transactionCode);

                if (! $payment) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Payment record not found',
                    ];
                }

                if (! $this->verifyGatewaySignature($gatewayData, $payment->payment_gateway)) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Invalid gateway signature',
                    ];
                }

                if ($payment->payment_status !== PaymentStatus::PENDING->value) {
                    return [
                        'status' => HttpStatusCode::SUCCESS->value,
                        'message' => 'Payment already processed',
                    ];
                }

                $newPaymentStatus = ($status === 'success') ? PaymentStatus::PAID->value : PaymentStatus::FAILED->value;

                $this->paymentRepository->update($payment->id, [
                    'payment_status' => $newPaymentStatus,
                    'gateway_response' => $gatewayData,
                    'paid_at' => ($status === 'success') ? now() : null,
                ]);

                if ($status === 'success') {
                    $this->bookingRepository->updatePaymentStatus($payment->booking_id, PaymentStatus::PAID->value);
                    $this->bookingRepository->updateStatus($payment->booking_id, BookingStatus::CONFIRMED->value);
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Payment callback handled successfully',
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to handle callback',
            ];
        }
    }

    private function verifyGatewaySignature(array $gatewayData, ?string $gateway): bool
    {
        $g = strtolower((string) $gateway);

        if (isset($gatewayData['vnp_SecureHash']) || $g === 'vnpay') {
            $secret = config('services.vnpay.hash_secret') ?? env('VNPAY_HASH_SECRET');
            if (! $secret) {
                Log::warning('VNPay secret is not configured. Webhook verification failed.');

                return false;
            }
            $params = [];
            foreach ($gatewayData as $k => $v) {
                if (str_starts_with($k, 'vnp_') && $k !== 'vnp_SecureHash' && $k !== 'vnp_SecureHashType') {
                    $params[$k] = $v;
                }
            }
            ksort($params);
            $data = urldecode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
            $calculated = strtoupper(hash_hmac('sha512', $data, $secret));
            $secureHash = strtoupper((string) ($gatewayData['vnp_SecureHash'] ?? ''));

            return hash_equals($calculated, $secureHash);
        }

        if (isset($gatewayData['signature']) || $g === 'momo') {
            $secret = config('services.momo.secret_key') ?? env('MOMO_SECRET_KEY');
            if (! $secret) {
                Log::warning('MoMo secret is not configured. Webhook verification failed.');

                return false;
            }
            $params = $gatewayData;
            unset($params['signature'], $params['sign'], $params['hash']);
            ksort($params);
            $data = urldecode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
            $calculated = hash_hmac('sha256', $data, $secret);
            $sig = (string) ($gatewayData['signature'] ?? '');

            return hash_equals($calculated, $sig);
        }

        if (isset($gatewayData['mac']) || $g === 'zalopay') {
            $secret = config('services.zalopay.key1') ?? env('ZALOPAY_KEY1');
            if (! $secret) {
                Log::warning('ZaloPay secret is not configured. Webhook verification failed.');

                return false;
            }
            $params = $gatewayData;
            unset($params['mac']);
            ksort($params);
            $data = urldecode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
            $calculated = hash_hmac('sha256', $data, $secret);
            $mac = (string) ($gatewayData['mac'] ?? '');

            return hash_equals($calculated, $mac);
        }

        return true;
    }

    /**
     * Get payment status.
     * (Lấy trạng thái thanh toán)
     */
    public function getStatus(string $transactionCode): array
    {
        $payment = $this->paymentRepository->findByTransactionCode($transactionCode);

        if (! $payment) {
            return [
                'status' => HttpStatusCode::NOT_FOUND->value,
                'message' => 'Payment not found',
            ];
        }

        return [
            'status' => HttpStatusCode::SUCCESS->value,
            'data' => [
                'payment_status' => $payment->payment_status,
                'transaction_code' => $payment->transaction_code,
                'booking_id' => $payment->booking_id,
            ],
            'message' => 'Payment status retrieved successfully',
        ];
    }

    /**
     * Retry payment for a booking.
     * (Thử thanh toán lại cho một đơn đặt chỗ)
     */
    public function retryPayment(string $bookingCode): array
    {
        $booking = $this->bookingRepository->findByCode($bookingCode);

        if (! $booking) {
            return [
                'status' => HttpStatusCode::NOT_FOUND->value,
                'message' => 'Booking not found',
            ];
        }

        if ($booking->payment_status === PaymentStatus::PAID->value) {
            return [
                'status' => HttpStatusCode::BAD_REQUEST->value,
                'message' => 'This booking has already been paid',
            ];
        }

        // Get last payment method if exists, otherwise default to MoMo
        $lastPayment = $booking->payments()->latest()->first();
        $paymentMethod = $lastPayment ? $lastPayment->payment_method : 'momo';

        return $this->createPayment([
            'booking_id' => $booking->id,
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Refund a payment.
     * (Hoàn tiền thanh toán)
     */
    public function refund(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $payment = $this->paymentRepository->find($id);

                if (! $payment) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Payment not found',
                    ];
                }

                if ($payment->payment_status !== PaymentStatus::PAID->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Only paid payments can be refunded',
                    ];
                }

                $this->paymentRepository->update($id, [
                    'payment_status' => PaymentStatus::REFUNDED->value,
                    'refunded_at' => now(),
                    'refund_reason' => $data['refund_reason'],
                ]);

                $this->bookingRepository->updatePaymentStatus($payment->booking_id, PaymentStatus::REFUNDED->value);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Payment refunded successfully',
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to refund payment',
            ];
        }
    }

    /**
     * Get all payments for admin.
     * (Lấy tất cả thanh toán cho quản trị viên)
     */
    public function getPayments(array $filters): array
    {
        $payments = $this->paymentRepository->getPayments($filters);

        return [
            'status' => HttpStatusCode::SUCCESS->value,
            'data' => $payments,
            'message' => 'Payments retrieved successfully',
        ];
    }

    /**
     * Get payment details for admin.
     * (Lấy chi tiết thanh toán cho quản trị viên)
     */
    public function getPayment(int $id): array
    {
        $payment = $this->paymentRepository->find($id);

        if (! $payment) {
            return [
                'status' => HttpStatusCode::NOT_FOUND->value,
                'message' => 'Payment not found',
            ];
        }

        return [
            'status' => HttpStatusCode::SUCCESS->value,
            'data' => $payment,
            'message' => 'Payment retrieved successfully',
        ];
    }

    /**
     * Get payments for export.
     * (Lấy danh sách thanh toán để xuất file)
     */
    public function getExportPayments(array $filters): array
    {
        $payments = $this->paymentRepository->getExportPayments($filters);

        return [
            'status' => HttpStatusCode::SUCCESS->value,
            'data' => $payments,
            'message' => 'Payments retrieved for export',
        ];
    }
}
