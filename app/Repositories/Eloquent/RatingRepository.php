<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Rating;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class RatingRepository
 * Eloquent implementation of RatingRepositoryInterface.
 * (Thực thi Eloquent cho RatingRepositoryInterface)
 */
final class RatingRepository extends BaseRepository implements RatingRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Rating::class;
    }

    /**
     * Paginate ratings for admin moderation.
     * (Phân trang danh sách đánh giá cho admin)
     */
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['user', 'location', 'images', 'approver'])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find rating with relations.
     * (Tìm đánh giá kèm quan hệ)
     */
    public function findWithRelations(int $id, array $relations = []): ?Rating
    {
        $query = $this->model->newQuery();
        if (count($relations) > 0) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Find rating for update (row lock) with relations.
     * (Tìm đánh giá để cập nhật (khóa dòng) kèm quan hệ)
     */
    public function findForUpdate(int $id, array $relations = []): ?Rating
    {
        $query = $this->model->newQuery()->lockForUpdate();
        if (count($relations) > 0) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Increment helpful_count if rating is approved.
     * (Tăng helpful_count nếu đánh giá đã được duyệt)
     */
    public function incrementHelpfulIfApproved(int $id): int
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('status', 'approved')
            ->increment('helpful_count');
    }

    /**
     * Get approved rating stats for a location.
     * (Lấy thống kê đánh giá đã duyệt cho một địa điểm)
     *
     * @return array{review_count:int,avg_rating:float}
     */
    public function getApprovedStatsForLocation(int $locationId): array
    {
        $row = $this->model->newQuery()
            ->where('location_id', $locationId)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as review_count, AVG(score) as avg_rating')
            ->first();

        return [
            'review_count' => (int) ($row->review_count ?? 0),
            'avg_rating' => round((float) ($row->avg_rating ?? 0), 1),
        ];
    }

    /**
     * Get ratings by user with filters and pagination.
     * (Lấy danh sách đánh giá của người dùng với bộ lọc và phân trang)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['location', 'images'])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
