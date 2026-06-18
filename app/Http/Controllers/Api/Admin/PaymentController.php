<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CompleteRefundRequest;
use App\Http\Requests\Payment\FiltersPaymentRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Http\Requests\Payment\ShowPaymentRequest;
use App\Models\RefundRequest;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class PaymentController
 * (Xử lý các yêu cầu quản trị liên quan đến thanh toán)
 */
final class PaymentController extends Controller
{
    /**
     * PaymentController constructor.
     * (Hàm khởi tạo)
     */
    public function __construct(
        protected PaymentService $paymentService,
        protected RefundService $refundService
    ) {}

    /**
     * List all payments.
     * (Danh sách tất cả thanh toán)
     */
    public function index(FiltersPaymentRequest $request): JsonResponse
    {
        $result = $this->paymentService->getPayments($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Show payment details.
     * (Xem chi tiết thanh toán)
     */
    public function show(ShowPaymentRequest $request, int $id): JsonResponse
    {
        $result = $this->paymentService->getPayment($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Refund a payment.
     * (Hoàn tiền thanh toán)
     */
    public function refund(RefundPaymentRequest $request, int $id): JsonResponse
    {
        $result = $this->paymentService->refund($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'] ?? null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    public function refunds(): JsonResponse
    {
        $refunds = RefundRequest::query()
            ->with(['booking', 'payment'])
            ->latest()
            ->paginate(20);
        $refunds->setCollection($refunds->getCollection()->map(
            fn (RefundRequest $refund) => $this->refundService->adminPayload($refund, true)
        ));

        return $this->success($refunds, 'Refund requests retrieved successfully.');
    }

    public function showRefund(int $id): JsonResponse
    {
        $refund = RefundRequest::query()->with(['booking', 'payment'])->find($id);
        if (! $refund) {
            return $this->error('Refund request not found.', HttpStatusCode::NOT_FOUND->value);
        }

        return $this->success($this->refundService->adminPayload($refund), 'Refund request retrieved successfully.');
    }

    public function completeRefund(CompleteRefundRequest $request, int $id): JsonResponse
    {
        $refund = RefundRequest::query()->find($id);
        if (! $refund) {
            return $this->error('Refund request not found.', HttpStatusCode::NOT_FOUND->value);
        }

        try {
            $completed = $this->refundService->complete(
                $refund,
                (int) $request->user()->id,
                $request->validated()
            );

            return $this->success($this->refundService->adminPayload($completed), 'Refund completed successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), HttpStatusCode::VALIDATION_ERROR->value);
        }
    }

    /**
     * Export payments to Excel.
     * (Xuất danh sách thanh toán ra file Excel)
     */
    public function export(FiltersPaymentRequest $request): BinaryFileResponse|JsonResponse
    {
        $result = $this->paymentService->getExportPayments($request->validated());

        return Excel::download(new PaymentsExport($result['data']), 'payments.xlsx');
    }
}
