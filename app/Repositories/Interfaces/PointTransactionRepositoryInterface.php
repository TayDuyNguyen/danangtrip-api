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

    /**
     * Get point transaction stats grouped by type and date.
     * (Lấy thống kê giao dịch điểm theo loại và ngày)
     */
    public function getStatsByTypeAndDate(?string $fromDate = null, ?string $toDate = null, ?string $type = null): array;
}
