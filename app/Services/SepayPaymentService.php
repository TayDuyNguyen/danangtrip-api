<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SepayPaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected BookingRepositoryInterface $bookingRepository,
        protected BookingPaymentNotificationService $paymentNotificationService,
        protected PointService $pointService,
        protected RefundService $refundService
    ) {}

    public function buildCheckoutPayload(Payment $payment, Booking $booking, ?string $returnUrl = null): array
    {
        $amount = $this->normalizeAmount(
            $payment->amount ?? $booking->final_amount ?? $booking->total_amount ?? 0
        );
        if ($amount <= 0) {
            throw new InvalidArgumentException('VietQR amount must be greater than zero.');
        }

        $transferContent = $this->transferContent((string) $booking->booking_code);
        $bankCode = (string) config('services.vietqr.bank_code');
        $accountNo = (string) config('services.vietqr.account_no');
        $accountName = (string) config('services.vietqr.account_name');

        return [
            'provider' => 'sepay',
            'merchant_id' => config('services.sepay.merchant_id'),
            'environment' => config('services.sepay.environment', 'sandbox'),
            'transaction_code' => $payment->transaction_code,
            'booking_code' => $booking->booking_code,
            'amount' => $amount,
            'currency' => 'VND',
            'transfer_content' => $transferContent,
            'qr_content' => $transferContent,
            'qr_image_url' => $this->buildVietQrImageUrl($amount, $transferContent, $bankCode, $accountNo, $accountName),
            'return_url' => $this->buildReturnUrl($returnUrl, $payment->transaction_code, (string) $booking->booking_code),
            'bank' => [
                'bank_code' => $bankCode,
                'account_no' => $accountNo,
                'account_name' => $accountName,
            ],
        ];
    }

    public function handleIpn(array $payload, array $headers = [], ?string $rawBody = null): array
    {
        if (! $this->verifyIpn($payload, $headers, $rawBody)) {
            Log::warning('SEPAY_IPN_SIGNATURE_INVALID', [
                'has_authorization' => $this->header($headers, 'authorization') !== null,
                'has_token_header' => $this->header($headers, 'x-sepay-token') !== null || $this->header($headers, 'x-webhook-token') !== null,
                'has_signature_header' => $this->header($headers, 'x-sepay-signature') !== null || $this->header($headers, 'x-signature') !== null,
                'payload_keys' => array_keys($payload),
            ]);

            return [
                'status' => HttpStatusCode::FORBIDDEN->value,
                'message' => 'Invalid SePay IPN signature',
            ];
        }

        $content = $this->extractTransferContent($payload);
        $amount = $this->extractAmount($payload);
        $reference = $this->extractReference($payload);
        $bookingCode = $this->extractBookingCode($content);

        if (! $bookingCode || $amount <= 0) {
            Log::warning('SEPAY_IPN_PAYLOAD_INVALID', [
                'content' => Str::limit($content, 160, ''),
                'amount' => $amount,
                'reference' => $reference,
                'payload_keys' => array_keys($payload),
            ]);

            return [
                'status' => HttpStatusCode::BAD_REQUEST->value,
                'message' => 'Invalid SePay IPN payload',
            ];
        }

        try {
            return DB::transaction(function () use ($payload, $amount, $bookingCode, $reference, $content) {
                $booking = Booking::query()
                    ->where('booking_code', $bookingCode)
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    Log::warning('SEPAY_IPN_BOOKING_NOT_FOUND', [
                        'booking_code' => $bookingCode,
                        'reference' => $reference,
                    ]);

                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found',
                    ];
                }

                if (in_array($booking->booking_status, [BookingStatus::CANCELLED->value, BookingStatus::COMPLETED->value], true)) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'Booking cannot accept additional payments',
                    ];
                }

                $expectedAmount = $this->normalizeAmount(
                    $booking->final_amount ?? $booking->total_amount ?? 0
                );
                $gatewayTransactionId = $reference ?: 'payload-'.hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
                $existingReceipt = PaymentReceipt::query()
                    ->where('gateway_transaction_id', $gatewayTransactionId)
                    ->first();
                if ($existingReceipt) {
                    return [
                        'status' => HttpStatusCode::SUCCESS->value,
                        'data' => [
                            'booking_code' => $booking->booking_code,
                            'already_processed' => true,
                        ],
                        'message' => 'Payment receipt already processed',
                    ];
                }

                $payment = $this->paymentRepository->findLatestPendingByBookingIdForUpdate((int) $booking->id);

                if (! $payment) {
                    $payment = $this->paymentRepository->create([
                        'booking_id' => $booking->id,
                        'transaction_code' => $this->buildSepayTransactionCode($reference, (string) $booking->booking_code),
                        'amount' => $amount,
                        'payment_method' => PaymentMethod::SEPAY->value,
                        'payment_status' => PaymentStatus::PENDING->value,
                        'payment_gateway' => 'sepay',
                    ]);
                }

                PaymentReceipt::query()->create([
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                    'gateway' => 'sepay',
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'amount' => $amount,
                    'transfer_content' => Str::limit($content, 255, ''),
                    'gateway_payload' => $payload,
                    'received_at' => now(),
                ]);

                $totalReceived = (int) round((float) PaymentReceipt::query()
                    ->where('booking_id', $booking->id)
                    ->sum('amount'));
                $shortAmount = max($expectedAmount - $totalReceived, 0);
                $excessAmount = max($totalReceived - $expectedAmount, 0);
                $isPaid = $totalReceived >= $expectedAmount;
                $reconciliationStatus = $excessAmount > 0
                    ? 'excess'
                    : ($shortAmount > 0 ? 'partial' : 'matched');
                $gatewayResponse = [
                    'provider' => 'sepay',
                    'reference' => $reference,
                    'payload' => $payload,
                ];
                if (! $isPaid) {
                    $remainingPayment = clone $payment;
                    $remainingPayment->setAttribute('amount', $shortAmount);
                    $gatewayResponse['checkout'] = $this->buildCheckoutPayload(
                        $remainingPayment,
                        $booking
                    );
                }

                $this->paymentRepository->update((int) $payment->id, [
                    'payment_status' => $isPaid ? PaymentStatus::SUCCESS->value : PaymentStatus::PENDING->value,
                    'payment_gateway' => 'sepay',
                    'received_amount' => $totalReceived,
                    'short_amount' => $shortAmount,
                    'excess_amount' => $excessAmount,
                    'is_discrepancy' => $reconciliationStatus !== 'matched',
                    'reconciliation_status' => $reconciliationStatus,
                    'gateway_response' => $gatewayResponse,
                    'paid_at' => $isPaid ? now() : null,
                ]);

                if (! $isPaid) {
                    $this->bookingRepository->updatePaymentStatus((int) $booking->id, 'partially_paid');
                    if ($booking->user_id) {
                        Notification::query()->create([
                            'user_id' => $booking->user_id,
                            'type' => 'payment_partially_received',
                            'title' => 'Đã nhận một phần thanh toán',
                            'content' => "Đơn {$booking->booking_code} đã nhận ".number_format($totalReceived, 0, ',', '.').'đ, còn thiếu '.number_format($shortAmount, 0, ',', '.').'đ.',
                            'data' => [
                                'booking_id' => $booking->id,
                                'received_amount' => $totalReceived,
                                'short_amount' => $shortAmount,
                                'gateway_transaction_id' => $gatewayTransactionId,
                            ],
                            'is_read' => false,
                        ]);
                    }
                } else {
                    $wasAlreadyPaid = $booking->payment_status === PaymentStatus::SUCCESS->value;
                    $this->bookingRepository->updatePaymentStatus((int) $booking->id, PaymentStatus::SUCCESS->value);
                    $this->bookingRepository->updateStatus((int) $booking->id, BookingStatus::CONFIRMED->value);
                    Payment::query()
                        ->where('booking_id', $booking->id)
                        ->where('id', '!=', $payment->id)
                        ->where('payment_status', PaymentStatus::PENDING->value)
                        ->update([
                            'payment_status' => PaymentStatus::FAILED->value,
                            'reconciliation_status' => 'superseded',
                            'updated_at' => now(),
                        ]);
                    if (! $wasAlreadyPaid) {
                        $this->paymentNotificationService->sendPaymentConfirmedAfterCommit((int) $booking->id);
                        if ($booking->user_id) {
                            $this->pointService->awardPoints(
                                (int) $booking->user_id,
                                'booking_paid',
                                'booking',
                                (int) $booking->id,
                                'Thưởng điểm thanh toán đơn '.$booking->booking_code
                            );
                        }
                    }
                    if ($excessAmount > 0) {
                        $booking->refresh()->loadMissing(['items.tour', 'payments', 'paymentReceipts']);
                        $this->refundService->createOverpaymentRequest($booking, $payment->fresh(), $excessAmount);
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'booking_code' => $booking->booking_code,
                        'transaction_code' => $payment->transaction_code,
                        'received_amount' => $amount,
                        'total_received' => $totalReceived,
                        'expected_amount' => $expectedAmount,
                        'short_amount' => $shortAmount,
                        'excess_amount' => $excessAmount,
                        'reconciliation_status' => $reconciliationStatus,
                    ],
                    'message' => 'SePay IPN handled successfully',
                ];
            });
        } catch (\Throwable) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to handle SePay IPN',
            ];
        }
    }

    public function transferContent(string $bookingCode): string
    {
        $prefix = trim((string) config('services.sepay.payment_prefix', 'DNT'));

        return trim($prefix.' '.$bookingCode);
    }

    private function buildVietQrImageUrl(int $amount, string $transferContent, string $bankCode, string $accountNo, string $accountName): string
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('VietQR amount must be greater than zero.');
        }

        $baseUrl = rtrim((string) config('services.vietqr.image_base_url'), '/');
        $template = (string) config('services.vietqr.template', 'compact2');

        return sprintf(
            '%s/%s-%s-%s.png?%s',
            $baseUrl,
            rawurlencode($bankCode),
            rawurlencode($accountNo),
            rawurlencode($template),
            http_build_query([
                'amount' => $amount,
                'addInfo' => $transferContent,
                'accountName' => $accountName,
            ], '', '&', PHP_QUERY_RFC3986)
        );
    }

    private function buildReturnUrl(?string $returnUrl, string $transactionCode, string $bookingCode): ?string
    {
        if (! $returnUrl) {
            return null;
        }

        $separator = str_contains($returnUrl, '?') ? '&' : '?';

        return $returnUrl.$separator.http_build_query([
            'transaction_code' => $transactionCode,
            'booking_code' => $bookingCode,
        ]);
    }

    private function verifyIpn(array $payload, array $headers, ?string $rawBody): bool
    {
        if (! (bool) config('services.sepay.verify_ipn_signature', false)) {
            return true;
        }

        $secret = (string) config('services.sepay.ipn_secret');
        if ($secret === '') {
            return false;
        }

        $authorization = $this->header($headers, 'authorization');
        if (is_string($authorization) && hash_equals('Bearer '.$secret, trim($authorization))) {
            return true;
        }

        $token = $this->header($headers, 'x-sepay-token') ?? $this->header($headers, 'x-webhook-token');
        if (is_string($token) && hash_equals($secret, trim($token))) {
            return true;
        }

        $signature = $this->header($headers, 'x-sepay-signature') ?? $this->header($headers, 'x-signature');
        if (is_string($signature) && $rawBody !== null) {
            $timestamp = $this->header($headers, 'x-sepay-timestamp') ?? $this->header($headers, 'x-timestamp');
            $signatureClean = trim($signature);
            if (str_starts_with($signatureClean, 'sha256=')) {
                $signatureClean = substr($signatureClean, 7);
            }

            if ($timestamp !== null && $timestamp !== '') {
                $dataToVerify = $timestamp.'.'.$rawBody;
            } else {
                $dataToVerify = $rawBody;
            }

            $calculated = hash_hmac('sha256', $dataToVerify, $secret);

            return hash_equals($calculated, $signatureClean);
        }

        if (isset($payload['token']) && is_string($payload['token'])) {
            return hash_equals($secret, $payload['token']);
        }

        return false;
    }

    private function header(array $headers, string $key): ?string
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === strtolower($key)) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }

    private function extractTransferContent(array $payload): string
    {
        foreach (['content', 'transferContent', 'transfer_content', 'description', 'transaction_content', 'payment_content'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return '';
    }

    private function extractAmount(array $payload): int
    {
        foreach (['transferAmount', 'transfer_amount', 'amount', 'value', 'money', 'creditAmount'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                return $this->normalizeAmount($payload[$key]);
            }
        }

        return 0;
    }

    private function extractReference(array $payload): ?string
    {
        foreach (['referenceCode', 'reference_code', 'transactionId', 'transaction_id', 'id', 'code'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return null;
    }

    private function extractBookingCode(string $content): ?string
    {
        $prefix = preg_quote((string) config('services.sepay.payment_prefix', 'DNT'), '/');
        if (preg_match('/\b'.$prefix.'\s*([A-Za-z0-9_-]{1,20})\b/i', $content, $matches)) {
            return $this->normalizeBookingCode($matches[1]);
        }

        if (preg_match('/\b(BK-[A-Za-z0-9_-]{1,17})\b/i', $content, $matches)) {
            return $this->normalizeBookingCode($matches[1]);
        }

        if (preg_match('/\b(BOOK-?[A-Za-z0-9_-]{1,16})\b/i', $content, $matches)) {
            return $this->normalizeBookingCode($matches[1]);
        }

        return null;
    }

    private function normalizeBookingCode(string $bookingCode): string
    {
        $normalized = strtoupper(trim($bookingCode));

        if (preg_match('/^BOOK([A-Z0-9_-]+)$/', $normalized, $matches)) {
            return 'BOOK-'.$matches[1];
        }

        return $normalized;
    }

    private function normalizeAmount(mixed $amount): int
    {
        if (is_int($amount) || is_float($amount)) {
            return (int) round($amount);
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $amount));
        $normalized = str_replace(',', '', $normalized ?? '');

        if ($normalized === '' || $normalized === '-' || ! is_numeric($normalized)) {
            return 0;
        }

        return (int) round((float) $normalized);
    }

    private function buildSepayTransactionCode(?string $reference, string $bookingCode): string
    {
        $suffix = $reference ? Str::upper(Str::slug($reference, '-')) : $bookingCode.'-'.time();

        return 'SEPAY-'.Str::limit($suffix, 88, '');
    }
}
