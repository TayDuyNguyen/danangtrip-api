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
}
