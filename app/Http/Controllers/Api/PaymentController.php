<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PaymentController
 * (Xử lý các yêu cầu thanh toán công khai và được bảo vệ)
 */
final class PaymentController extends Controller
{
    /**
     * PaymentController constructor.
     * (Hàm khởi tạo)
     */
    public function __construct(protected PaymentService $paymentService) {}

    /**
     * Handle payment callback from gateway.
     * (Xử lý phản hồi từ cổng thanh toán)
     */
    public function callback(Request $request): JsonResponse
    {
        // For callback, we usually don't validate strictly because it's gateway-specific
        $result = $this->paymentService->handleCallback($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Create payment link.
     * (Tạo link thanh toán)
     */
    public function create(CreatePaymentRequest $request): JsonResponse
    {
        $result = $this->paymentService->createPayment($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get payment status.
     * (Lấy trạng thái thanh toán)
     */
    public function status(string $transactionCode): JsonResponse
    {
        $result = $this->paymentService->getStatus($transactionCode);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Retry payment for a booking.
     * (Thử thanh toán lại cho một đơn đặt chỗ)
     */
    public function retry(string $bookingCode): JsonResponse
    {
        $result = $this->paymentService->retryPayment($bookingCode);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
