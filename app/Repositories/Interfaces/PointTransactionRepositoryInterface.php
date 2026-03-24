<?php

namespace App\Repositories\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface PointTransactionRepositoryInterface
 * (Giao diện Repository cho Giao dịch Điểm)
 */
interface PointTransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated transactions for a user.
     * (Lấy danh sách giao dịch có phân trang cho người dùng)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator;

    /**
     * Generate a unique transaction code.
     * (Tạo mã giao dịch duy nhất)
     */
    public function generateTransactionCode(): string;
}
