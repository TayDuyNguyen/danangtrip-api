<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HttpStatusCode;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SepayPaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected BookingRepositoryInterface $bookingRepository,
        protected BookingPaymentNotificationService $paymentNotificationService
    ) {}

    public function buildCheckoutPayload(Payment $payment, Booking $booking, ?string $returnUrl = null): array
    {
        $amount = $this->normalizeAmount($payment->amount ?: $booking->final_amount ?: $booking->total_amount);
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
            return [
                'status' => HttpStatusCode::BAD_REQUEST->value,
                'message' => 'Invalid SePay IPN payload',
            ];
        }

        try {
            return DB::transaction(function () use ($payload, $amount, $bookingCode, $reference) {
                $booking = $this->bookingRepository->findByCode($bookingCode);

                if (! $booking) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Booking not found',
                    ];
                }

                $expectedAmount = $this->normalizeAmount($booking->final_amount ?: $booking->total_amount);
                if ($amount !== $expectedAmount) {
                    return [
                        'status' => HttpStatusCode::BAD_REQUEST->value,
                        'message' => 'SePay IPN amount does not match booking amount',
                    ];
                }

                if ($booking->payment_status === PaymentStatus::SUCCESS->value) {
                    return [
                        'status' => HttpStatusCode::SUCCESS->value,
                        'data' => [
                            'booking_code' => $booking->booking_code,
                            'already_paid' => true,
                        ],
                        'message' => 'Payment already processed',
                    ];
                }

                $payment = $this->paymentRepository->findLatestPendingByBookingIdForUpdate((int) $booking->id);

                if (! $payment) {
                    $payment = $this->paymentRepository->create([
                        'booking_id' => $booking->id,
                        'transaction_code' => $this->buildSepayTransactionCode($reference, (string) $booking->booking_code),
                        'amount' => $expectedAmount,
                        'payment_method' => PaymentMethod::SEPAY->value,
                        'payment_status' => PaymentStatus::PENDING->value,
                        'payment_gateway' => 'sepay',
                    ]);
                }

                $this->paymentRepository->update((int) $payment->id, [
                    'payment_status' => PaymentStatus::SUCCESS->value,
                    'payment_gateway' => 'sepay',
                    'gateway_response' => [
                        'provider' => 'sepay',
                        'reference' => $reference,
                        'payload' => $payload,
                    ],
                    'paid_at' => now(),
                ]);

                $this->bookingRepository->updatePaymentStatus((int) $booking->id, PaymentStatus::SUCCESS->value);
                $this->bookingRepository->updateStatus((int) $booking->id, BookingStatus::CONFIRMED->value);
                $this->paymentNotificationService->sendPaymentConfirmedAfterCommit((int) $booking->id);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => [
                        'booking_code' => $booking->booking_code,
                        'transaction_code' => $payment->transaction_code,
                        'amount' => $expectedAmount,
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

        $secret = (string) config('services.sepay.secret_key');
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
            $calculated = hash_hmac('sha256', $rawBody, $secret);

            return hash_equals($calculated, trim($signature));
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
        $normalized = preg_replace('/[^\d.]/', '', (string) $amount);

        return (int) round((float) $normalized);
    }

    private function buildSepayTransactionCode(?string $reference, string $bookingCode): string
    {
        $suffix = $reference ? Str::upper(Str::slug($reference, '-')) : $bookingCode.'-'.time();

        return 'SEPAY-'.Str::limit($suffix, 88, '');
    }
}
