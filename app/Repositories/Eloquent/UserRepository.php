<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * Class UserRepository
 * Eloquent implementation of UserRepositoryInterface.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Get the associated model class name.
     *
     * @return string
     */
    public function getModel()
    {
        return User::class;
    }
}
