<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * Increment user point balance.
     * (Tăng số dư điểm của người dùng)
     */
    public function incrementPointBalance(int $userId, int $amount): bool;

    /**
     * Decrement user point balance.
     * (Giảm số dư điểm của người dùng)
     */
    public function decrementPointBalance(int $userId, int $amount): bool;

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
     * Get new users count grouped by month for a specific year.
     * (Lấy số lượng người dùng mới theo tháng trong một năm)
     */
    public function getNewUsersByMonth(int $year): array;
}
