<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\IndexPromotionRequest;
use App\Http\Requests\Promotion\StorePromotionRequest;
use App\Http\Requests\Promotion\UpdatePromotionRequest;
use App\Http\Requests\Promotion\UpdatePromotionStatusRequest;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;

/**
 * Class PromotionController (Admin)
 * Handles admin API requests for managing promotions/coupons.
 * (Xử lý các yêu cầu API quản trị cho Khuyến mãi / Mã giảm giá)
 */
final class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * List promotions with filters/pagination.
     * (Danh sách khuyến mãi — có tìm kiếm, lọc, phân trang)
     */
    public function index(IndexPromotionRequest $request): JsonResponse
    {
        $result = $this->promotionService->adminList($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get promotion detail.
     * (Chi tiết khuyến mãi)
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->promotionService->show($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Create a new promotion.
     * (Tạo khuyến mãi mới)
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $result = $this->promotionService->create($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->success($result['data'], $result['message'] ?? 'Promotion created.', HttpStatusCode::CREATED->value)
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a promotion.
     * (Cập nhật khuyến mãi)
     */
    public function update(UpdatePromotionRequest $request, int $id): JsonResponse
    {
        $result = $this->promotionService->update($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Promotion updated.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Toggle promotion status (active/inactive).
     * (Bật/Tắt trạng thái khuyến mãi)
     */
    public function updateStatus(UpdatePromotionStatusRequest $request, int $id): JsonResponse
    {
        $result = $this->promotionService->toggleStatus($id, $request->validated()['status']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Promotion status updated.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a promotion.
     * (Xóa khuyến mãi)
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->promotionService->delete($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Promotion deleted.')
            : $this->error($result['message'], $result['status']);
    }
}
