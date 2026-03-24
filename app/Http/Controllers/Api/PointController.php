<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\PointValidation;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PointController
 * (Điều khiển các hoạt động liên quan đến điểm thưởng)
 */
final class PointController extends Controller
{
    /**
     * PointController constructor.
     * (Khởi tạo PointController)
     */
    public function __construct(
        protected PointService $pointService
    ) {}

    /**
     * Get current point balance.
     * (Lấy số dư điểm hiện tại)
     */
    public function balance(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->pointService->getBalance($userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get transaction history.
     * (Lấy lịch sử giao dịch)
     */
    public function transactions(Request $request): JsonResponse
    {
        $validator = PointValidation::validateTransactions($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $userId = $request->user()->id;
        $result = $this->pointService->getTransactions($userId, $request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Purchase points.
     * (Nạp điểm)
     */
    public function purchase(Request $request): JsonResponse
    {
        $validator = PointValidation::validatePurchase($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $userId = $request->user()->id;
        $result = $this->pointService->purchasePoints($userId, $validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
