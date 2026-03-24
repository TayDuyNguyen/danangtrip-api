<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\PointTransaction;
use App\Repositories\Interfaces\PointTransactionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Class PointTransactionRepository
 * (Lớp triển khai Repository cho Giao dịch Điểm bằng Eloquent)
 */
final class PointTransactionRepository extends BaseRepository implements PointTransactionRepositoryInterface
{
    /**
     * Specify model class name.
     * (Chỉ định tên lớp Model)
     */
    public function getModel(): string
    {
        return PointTransaction::class;
    }

    /**
     * Get paginated transactions for a user.
     * (Lấy danh sách giao dịch có phân trang cho người dùng)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Generate a unique transaction code.
     * (Tạo mã giao dịch duy nhất)
     */
    public function generateTransactionCode(): string
    {
        do {
            $code = 'PT'.strtoupper(Str::random(10));
        } while ($this->model->where('transaction_code', $code)->exists());

        return $code;
    }
}
