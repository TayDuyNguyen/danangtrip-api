<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\PaymentCallbackRequest;
use App\Http\Requests\Payment\RetryPaymentRequest;
use App\Http\Requests\Payment\ShowPaymentStatusRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

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
    public function callback(PaymentCallbackRequest $request): JsonResponse
    {
        // Use validated data for standard keys, but some gateways might send unexpected fields
        // that our service needs (like full raw payload for signature verification).
        // PaymentService::handleCallback(array $gatewayData) handles the logic.
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
    public function status(ShowPaymentStatusRequest $request): JsonResponse
    {
        $result = $this->paymentService->getStatus($request->validated()['transaction_code']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Retry payment for a booking.
     * (Thử thanh toán lại cho một đơn đặt chỗ)
     */
    public function retry(RetryPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->paymentService->retryPayment($validated['booking_code'], $validated['return_url'] ?? null);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
