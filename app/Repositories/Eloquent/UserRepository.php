<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by username.
     * (Tìm người dùng theo tên đăng nhập)
     */
    public function findByUsername(string $username): ?User
    {
        return $this->model->where('username', $username)->first();
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

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get user detail with stats.
     * (Lấy chi tiết người dùng kèm thống kê)
     */
    public function getUserWithStats(int $id): ?User
    {
        return $this->model->newQuery()
            ->withCount(['ratings'])
            ->find($id);
    }

    /**
     * Get total user count.
     * (Lấy tổng số người dùng)
     */
    public function getTotalCount(): int
    {
        return $this->model->count();
    }

    /**
     * Get new users count grouped by month for a specific year.
     * (Lấy số lượng người dùng mới theo tháng trong một năm)
     */
    public function getNewUsersByMonth(int $year): array
    {
        return $this->model->newQuery()
            ->selectRaw('EXTRACT(MONTH FROM created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}
