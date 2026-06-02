<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class PaymentService
 * (Xử lý logic nghiệp vụ liên quan đến thanh toán)
 */
class PaymentService
{
    private const CALLBACK_GATEWAYS = [
        PaymentMethod::MOMO->value,
        PaymentMethod::VNPAY->value,
        PaymentMethod::ZALOPAY->value,
        PaymentMethod::PAYOS->value,
    ];

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
                $currentUserId = Auth::id();
                if ($currentUserId !== null && (int) $booking->user_id !== (int) $currentUserId) {
                    return [
                        'status' => HttpStatusCode::FORBIDDEN->value,
                        'message' => 'You do not have permission to pay this booking',
                    ];
                }

                if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'This booking has already been paid',
                    ];
                }

                $transactionCode = 'PAY-'.strtoupper(Str::random(10));

                $payment = $this->paymentRepository->create([
                    'booking_id' => $booking->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $booking->final_amount ?? $booking->total_amount,
                    'payment_method' => $data['payment_method'],
                    'payment_status' => PaymentStatus::PENDING->value,
                    'payment_gateway' => $data['payment_method'], // Mock gateway
                ]);

                // Mock payment link creation
                $amount = $booking->final_amount ?? $booking->total_amount;
                $paymentLink = $this->buildPaymentUrl(
                    $transactionCode,
                    (string) $booking->booking_code,
                    (string) $data['payment_method'],
                    (string) $amount,
                    $data['return_url'] ?? null,
                );

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'payment' => $payment,
                        'payment_url' => $paymentLink,
                        'transaction_code' => $transactionCode,
                        'booking_code' => $booking->booking_code,
                    ],
                    'message' => 'Payment link created successfully',
                ];
            });
        } catch (\Exception $e) {

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

                if (! $this->supportsSignedCallback($payment->payment_gateway)) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Unsupported payment gateway callback',
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

                $newPaymentStatus = ($status === 'success') ? PaymentStatus::SUCCESS->value : PaymentStatus::FAILED->value;

                $this->paymentRepository->update($payment->id, [
                    'payment_status' => $newPaymentStatus,
                    'gateway_response' => $gatewayData,
                    'paid_at' => ($status === 'success') ? now() : null,
                ]);

                if ($status === 'success') {
                    $this->bookingRepository->updatePaymentStatus($payment->booking_id, PaymentStatus::SUCCESS->value);
                    $this->bookingRepository->updateStatus($payment->booking_id, BookingStatus::CONFIRMED->value);
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Payment callback handled successfully',
                ];
            });
        } catch (\Exception $e) {

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

        if ($g === 'payos') {
            return true;
        }

        return false;
    }

    private function supportsSignedCallback(?string $gateway): bool
    {
        return in_array(strtolower((string) $gateway), self::CALLBACK_GATEWAYS, true);
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

        $currentUserId = Auth::id();
        $bookingUserId = $payment->booking?->user_id;
        if ($currentUserId === null || $bookingUserId === null || (int) $bookingUserId !== (int) $currentUserId) {
            return [
                'status' => HttpStatusCode::FORBIDDEN->value,
                'message' => 'You do not have permission to view this payment',
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
    public function retryPayment(string $bookingCode, ?string $returnUrl = null, ?string $paymentMethod = null): array
    {
        $booking = $this->bookingRepository->findByCode($bookingCode);

        if (! $booking) {
            return [
                'status' => HttpStatusCode::NOT_FOUND->value,
                'message' => 'Booking not found',
            ];
        }
        $currentUserId = Auth::id();
        if ($currentUserId !== null && (int) $booking->user_id !== (int) $currentUserId) {
            return [
                'status' => HttpStatusCode::FORBIDDEN->value,
                'message' => 'You do not have permission to retry this booking',
            ];
        }

        if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
            return [
                'status' => HttpStatusCode::BAD_REQUEST->value,
                'message' => 'This booking has already been paid',
            ];
        }

        // Use the requested method when the customer changes gateway; otherwise reuse the last method.
        $lastPayment = $booking->payments()->latest()->first();
        $paymentMethod = $paymentMethod ?: ($lastPayment ? $lastPayment->payment_method : PaymentMethod::PAYOS->value);

        return $this->createPayment([
            'booking_id' => $booking->id,
            'payment_method' => $paymentMethod,
            'return_url' => $returnUrl,
        ]);
    }

    private function buildPaymentUrl(
        string $transactionCode,
        string $bookingCode,
        string $paymentMethod,
        string $amount,
        ?string $returnUrl = null
    ): string {
        if ($returnUrl) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';

            return $returnUrl.$separator.http_build_query([
                'transaction_code' => $transactionCode,
                'booking_code' => $bookingCode,
            ]);
        }

        return "https://mock-gateway.com/pay/{$transactionCode}?method={$paymentMethod}&amount={$amount}";
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

                if ($payment->payment_status !== PaymentStatus::SUCCESS->value) {
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
