<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

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
     * Increment user point balance.
     * (Tăng số dư điểm của người dùng)
     */
    public function incrementPointBalance(int $userId, int $amount): bool
    {
        return (bool) $this->model->where('id', $userId)->increment('point_balance', $amount);
    }

    /**
     * Decrement user point balance.
     * (Giảm số dư điểm của người dùng)
     */
    public function decrementPointBalance(int $userId, int $amount): bool
    {
        return (bool) $this->model->where('id', $userId)->decrement('point_balance', $amount);
    }
}
