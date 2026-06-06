<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\PromotionRepositoryInterface;
use Exception;
use Illuminate\Support\Str;

/**
 * Class PromotionService
 * (Dịch vụ xử lý nghiệp vụ Khuyến mãi / Mã giảm giá)
 */
final class PromotionService
{
    public function __construct(
        protected PromotionRepositoryInterface $promotionRepository
    ) {}

    /**
     * Admin list promotions with filters.
     * (Danh sách khuyến mãi cho admin — có tìm kiếm/lọc)
     */
    public function adminList(array $filters): array
    {
        try {
            $paginator = $this->promotionRepository->adminList($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $paginator,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve promotions.',
            ];
        }
    }

    /**
     * Get a single promotion detail (admin).
     * (Chi tiết khuyến mãi — admin)
     */
    public function show(int $id): array
    {
        try {
            $promotion = $this->promotionRepository->find($id);

            if (! $promotion) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Promotion not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $promotion,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve promotion.',
            ];
        }
    }

    /**
     * Create a new promotion.
     * (Tạo khuyến mãi mới)
     */
    public function create(array $data): array
    {
        try {
            // Normalize code — always uppercase
            $data['code'] = Str::upper(trim($data['code']));

            // Check code uniqueness
            if ($this->promotionRepository->findByCode($data['code'])) {
                return [
                    'status' => HttpStatusCode::VALIDATION_ERROR->value,
                    'message' => 'Promotion code already exists.',
                ];
            }

            $promotion = $this->promotionRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $promotion,
                'message' => 'Promotion created successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create promotion.',
            ];
        }
    }

    /**
     * Update an existing promotion.
     * (Cập nhật khuyến mãi)
     */
    public function update(int $id, array $data): array
    {
        try {
            $promotion = $this->promotionRepository->find($id);

            if (! $promotion) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Promotion not found.',
                ];
            }

            // Normalize code
            if (isset($data['code'])) {
                $data['code'] = Str::upper(trim($data['code']));

                // Check uniqueness only if code changed
                if ($data['code'] !== $promotion->code) {
                    $existing = $this->promotionRepository->findByCode($data['code']);
                    if ($existing && $existing->id !== $id) {
                        return [
                            'status' => HttpStatusCode::VALIDATION_ERROR->value,
                            'message' => 'Promotion code already exists.',
                        ];
                    }
                }
            }

            $this->promotionRepository->update($id, $data);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->promotionRepository->find($id),
                'message' => 'Promotion updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update promotion.',
            ];
        }
    }

    /**
     * Toggle promotion active/inactive status.
     * (Bật/Tắt trạng thái khuyến mãi)
     */
    public function toggleStatus(int $id, string $status): array
    {
        try {
            $promotion = $this->promotionRepository->find($id);

            if (! $promotion) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Promotion not found.',
                ];
            }

            $this->promotionRepository->toggleStatus($id, $status);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Promotion status updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update promotion status.',
            ];
        }
    }

    /**
     * Delete a promotion.
     * (Xóa khuyến mãi)
     */
    public function delete(int $id): array
    {
        try {
            $promotion = $this->promotionRepository->find($id);

            if (! $promotion) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Promotion not found.',
                ];
            }

            $this->promotionRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Promotion deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete promotion.',
            ];
        }
    }

    /**
     * Get active promotions for public API.
     * (Danh sách khuyến mãi đang hoạt động — giao diện công khai)
     */
    public function getActivePromotions(): array
    {
        try {
            $promotions = $this->promotionRepository->getActivePromotions();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $promotions,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve promotions.',
            ];
        }
    }

    /**
     * Validate a promotion code for a given order total.
     * (Kiểm tra mã khuyến mãi cho một đơn hàng)
     */
    public function validateCode(string $code, float $orderTotal): array
    {
        try {
            $promotion = $this->promotionRepository->findByCode(strtoupper($code));

            if (! $promotion) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Promotion code not found.',
                ];
            }

            if (! $promotion->isValid()) {
                return [
                    'status' => HttpStatusCode::VALIDATION_ERROR->value,
                    'message' => 'Promotion is not currently valid.',
                ];
            }

            if ($orderTotal < (float) $promotion->min_order_amount) {
                return [
                    'status' => HttpStatusCode::VALIDATION_ERROR->value,
                    'message' => "Minimum order amount is {$promotion->min_order_amount}.",
                ];
            }

            $discountAmount = $promotion->calculateDiscount($orderTotal);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'promotion' => $promotion,
                    'discount_amount' => $discountAmount,
                    'final_amount' => max(0, $orderTotal - $discountAmount),
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to validate promotion code.',
            ];
        }
    }
}
