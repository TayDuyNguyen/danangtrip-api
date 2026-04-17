<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface UserRepositoryInterface
 * Define standard operations for User repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Người dùng)
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email.
     * (Tìm người dùng theo email)
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by username.
     * (Tìm người dùng theo tên đăng nhập)
     */
    public function findByUsername(string $username): ?User;

    /**
     * Get paginated users with filters.
     * (Lấy danh sách người dùng có phân trang và bộ lọc)
     */
    public function getUsersPaginated(array $filters): LengthAwarePaginator;

    /**
     * Get user detail with stats.
     * (Lấy chi tiết người dùng kèm thống kê)
     */
    public function getUserWithStats(int $id): ?User;

    /**
     * Get total user count.
     * (Lấy tổng số người dùng)
     */
    public function getTotalCount(): int;

    /**
     * Get new users count grouped by month for the last 12 months.
     * (Lấy số lượng người dùng mới theo tháng trong 12 tháng qua)
     */
    public function getNewUsersLast12Months(): array;

    /**
     * Get new users count grouped by month for a specific year.
     * (Lấy số lượng người dùng mới theo tháng trong một năm cụ thể)
     */
    public function getNewUsersByMonth(int $year): array;

    /**
     * Mark a user's email as verified.
     * (Đánh dấu email của người dùng là đã xác minh)
     */
    public function markEmailAsVerified(int $userId): bool;

    /**
     * Chunk all users.
     * (Duyệt qua tất cả người dùng theo từng đợt)
     */
    public function chunkAll(int $size, callable $callback): bool;

    /**
     * Get paginated bookings for a specific user.
     * (Lấy danh sách đặt tour có phân trang của một người dùng)
     */
    public function getUserBookingsPaginated(int $userId, array $filters): LengthAwarePaginator;

    /**
     * Get paginated ratings for a specific user.
     * (Lấy danh sách đánh giá có phân trang của một người dùng)
     */
    public function getUserRatingsPaginated(int $userId, array $filters): LengthAwarePaginator;

    /**
     * Get all users for export with optional filters.
     * (Lấy tất cả người dùng để export với bộ lọc tùy chọn)
     */
    public function getAllForExport(array $filters): Collection;
}
