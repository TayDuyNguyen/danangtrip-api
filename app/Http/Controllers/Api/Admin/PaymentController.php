<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\FiltersPaymentRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Services\PaymentService;
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
    public function __construct(protected PaymentService $paymentService) {}

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
    public function show(int $id): JsonResponse
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
