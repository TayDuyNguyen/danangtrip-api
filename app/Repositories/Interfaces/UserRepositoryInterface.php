<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

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
}
