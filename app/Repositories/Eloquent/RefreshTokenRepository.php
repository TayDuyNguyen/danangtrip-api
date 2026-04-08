<?php

namespace App\Repositories\Eloquent;

use App\Models\RefreshToken;
use App\Repositories\Interfaces\RefreshTokenRepositoryInterface;

/**
 * Class RefreshTokenRepository
 * (Thực thi Repository cho Refresh Token sử dụng Eloquent)
 */
class RefreshTokenRepository extends BaseRepository implements RefreshTokenRepositoryInterface
{
    /**
     * Get the model class name.
     *
     * @return string
     */
    public function getModel()
    {
        return RefreshToken::class;
    }

    /**
     * Find a token by its hashed value.
     *
     * @return mixed
     */
    public function findByToken(string $hashedToken)
    {
        return $this->model->where('token', $hashedToken)->with('user')->first();
    }

    /**
     * Delete a token by its hashed value.
     *
     * @return void
     */
    public function deleteByToken(string $hashedToken)
    {
        $this->model->where('token', $hashedToken)->delete();
    }

    /**
     * Delete all tokens belonging to a user.
     *
     * @return void
     */
    public function deleteAllByUserId(int $userId)
    {
        $this->model->where('user_id', $userId)->delete();
    }
}
