<?php

namespace App\Repositories\Interfaces;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface PromotionRepositoryInterface
 * (Giao diện Repository cho Khuyến mãi)
 */
interface PromotionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated list of promotions with optional filters.
     * (Lấy danh sách khuyến mãi có phân trang và lọc)
     */
    public function adminList(array $filters): LengthAwarePaginator;

    /**
     * Find promotion by unique code.
     * (Tìm khuyến mãi theo mã duy nhất)
     */
    public function findByCode(string $code): ?object;

    /**
     * Toggle status of a promotion.
     * (Đổi trạng thái khuyến mãi active/inactive)
     */
    public function toggleStatus(int $id, string $status): bool;

    /**
     * Get currently valid active promotions for public listing.
     * (Lấy danh sách khuyến mãi đang hợp lệ cho giao diện công khai)
     */
    public function getActivePromotions(): Collection;

    public function findByCodeForUpdate(string $code): ?Promotion;

    public function findForUpdate(int $id): ?Promotion;

    public function decrementUsedCountIfPositive(int $id): bool;
}
