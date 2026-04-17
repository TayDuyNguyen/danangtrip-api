<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class UserRepository
 * Eloquent implementation of UserRepositoryInterface.
 * (Thực thi Eloquent cho UserRepositoryInterface)
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return User::class;
    }

    /**
     * Find user by email.
     * (Tìm người dùng theo email)
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    /**
     * Find user by username.
     * (Tìm người dùng theo tên đăng nhập)
     */
    public function findByUsername(string $username): ?User
    {
        return $this->model->newQuery()->where('username', $username)->first();
    }

    /**
     * Get paginated users with filters.
     * (Lấy danh sách người dùng có phân trang và bộ lọc)
     */
    public function getUsersPaginated(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        $allowedSorts = ['id', 'username', 'email', 'full_name', 'created_at', 'status'];
        $sort = in_array($filters['sort_by'] ?? '', $allowedSorts) ? $filters['sort_by'] : 'created_at';
        $order = strtolower($filters['sort_order'] ?? '') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $order)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get user detail with stats.
     * (Lấy chi tiết người dùng kèm thống kê)
     */
    public function getUserWithStats(int $id): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->withCount(['ratings'])
            ->find($id);

        return $user;
    }

    /**
     * Get total user count.
     * (Lấy tổng số người dùng)
     */
    public function getTotalCount(): int
    {
        return $this->model->newQuery()->count();
    }

    /**
     * Get new users count grouped by month for the last 12 months.
     * (Lấy số lượng người dùng mới theo tháng trong 12 tháng qua)
     */
    public function getNewUsersLast12Months(): array
    {
        $table = $this->model->getTable();

        return $this->model->newQuery()
            ->selectRaw("TO_CHAR({$table}.created_at, 'YYYY-MM') as month, COUNT(*) as count")
            ->where("{$table}.created_at", '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw("TO_CHAR({$table}.created_at, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR({$table}.created_at, 'YYYY-MM')")
            ->get()
            ->toArray();
    }

    /**
     * Get new users count grouped by month for a specific year.
     * (Lấy số lượng người dùng mới theo tháng trong một năm cụ thể)
     */
    public function getNewUsersByMonth(int $year): array
    {
        $table = $this->model->getTable();

        return $this->model->newQuery()
            ->selectRaw("EXTRACT(MONTH FROM {$table}.created_at) as month, COUNT(*) as count")
            ->whereYear("{$table}.created_at", $year)
            ->groupByRaw("EXTRACT(MONTH FROM {$table}.created_at)")
            ->orderByRaw("EXTRACT(MONTH FROM {$table}.created_at)")
            ->get()
            ->toArray();
    }

    /**
     * Mark a user's email as verified.
     * (Đánh dấu email của người dùng là đã xác minh)
     */
    public function markEmailAsVerified(int $userId): bool
    {
        return (bool) $this->update($userId, [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Chunk all users.
     * (Duyệt qua tất cả người dùng theo từng đợt)
     */
    public function chunkAll(int $size, callable $callback): bool
    {
        return $this->model->newQuery()->select('id')->chunk($size, $callback);
    }

    /**
     * Get paginated bookings for a specific user.
     * (Lấy danh sách đặt tour có phân trang của một người dùng)
     */
    public function getUserBookingsPaginated(int $userId, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return Booking::query()
            ->with(['tourSchedule.tour'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get paginated ratings for a specific user.
     * (Lấy danh sách đánh giá có phân trang của một người dùng)
     */
    public function getUserRatingsPaginated(int $userId, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return Rating::query()
            ->with(['location:id,name', 'tour:id,name'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all users for export with optional filters.
     * (Lấy tất cả người dùng để export với bộ lọc tùy chọn)
     */
    public function getAllForExport(array $filters): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
