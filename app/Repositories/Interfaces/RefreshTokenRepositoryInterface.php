<?php

namespace App\Repositories\Interfaces;

/**
 * Interface RefreshTokenRepositoryInterface
 * (Giao diện Repository cho Refresh Token)
 */
interface RefreshTokenRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a token by its hashed value.
     * (Tìm token bằng giá trị hash)
     *
     * @return mixed
     */
    public function findByToken(string $hashedToken);

    /**
     * Delete a token by its hashed value.
     * (Xóa token bằng giá trị hash)
     *
     * @return void
     */
    public function deleteByToken(string $hashedToken);

    /**
     * Delete all tokens belonging to a user.
     * (Xóa tất cả token thuộc về một người dùng)
     *
     * @return void
     */
    public function deleteAllByUserId(int $userId);
}
