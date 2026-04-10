<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Rating;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
            ->with(['user', 'location', 'tour', 'images', 'approver'])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['tour_id'])) {
            $query->where('tour_id', $filters['tour_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find rating for update (row lock) with relations.
     * (Tìm đánh giá để cập nhật (khóa dòng) kèm quan hệ)
     */
    public function findForUpdate(int $id, array $relations = []): ?Rating
    {
        return $this->lockForUpdate()->with($relations)->find($id);
    }

    /**
     * Increment helpful_count if rating is approved.
     * (Tăng helpful_count nếu đánh giá đã được duyệt)
     */
    public function incrementHelpfulIfApproved(int $id): int
    {
        return (int) $this->increment($id, 'helpful_count', 1, [['status', 'approved']]);
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
     * Get approved rating stats for a tour.
     * (Lấy thống kê đánh giá đã duyệt cho một tour)
     *
     * @return array{review_count:int,avg_rating:float}
     */
    public function getApprovedStatsForTour(int $tourId): array
    {
        $row = $this->model->newQuery()
            ->where('tour_id', $tourId)
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
            ->with(['location', 'tour', 'images'])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Check if a user has already rated an item.
     * (Kiểm tra xem người dùng đã đánh giá một mục chưa)
     */
    public function checkUserRated(int $userId, array $params): ?Rating
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if (isset($params['location_id'])) {
            $query->where('location_id', $params['location_id']);
        } elseif (isset($params['tour_id'])) {
            $query->where('tour_id', $params['tour_id']);
        } elseif (isset($params['booking_id'])) {
            $query->where('booking_id', $params['booking_id']);
        }

        return $query->first();
    }

    /**
     * Collection of ratings for export.
     * (Duyệt danh sách đánh giá phục vụ export)
     */
    public function searchForExport(array $filters): Collection
    {
        $query = $this->model->newQuery()
            ->with(['user', 'location', 'tour', 'approver'])
            ->orderByDesc('created_at');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['tour_id'])) {
            $query->where('tour_id', $filters['tour_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Get rating stats grouped by date and status.
     * (Lấy thống kê đánh giá theo ngày và trạng thái)
     */
    public function getStatsByDateAndStatus(?string $fromDate = null, ?string $toDate = null, ?string $status = null): array
    {
        $query = $this->model->newQuery()
            ->selectRaw('CAST(created_at AS DATE) as date, status, COUNT(*) as count');

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }
        if ($status) {
            $query->where('status', $status);
        }

        return $query->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
