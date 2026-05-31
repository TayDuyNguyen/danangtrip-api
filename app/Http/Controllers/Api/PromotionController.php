<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\ValidatePromotionCodeRequest;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;

/**
 * Class PromotionController (Public)
 * Handles public API requests for promotions.
 * (Xử lý các yêu cầu API công khai cho Khuyến mãi)
 */
final class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * Get list of currently active promotions.
     * (Danh sách khuyến mãi đang hoạt động — công khai)
     */
    public function index(): JsonResponse
    {
        $result = $this->promotionService->getActivePromotions();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Validate a promotion code against an order total.
     * (Kiểm tra mã giảm giá so với tổng giá trị đơn hàng)
     */
    public function validate(ValidatePromotionCodeRequest $request): JsonResponse
    {
        $result = $this->promotionService->validateCode(
            $request->validated()['code'],
            (float) $request->validated()['order_total']
        );

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
