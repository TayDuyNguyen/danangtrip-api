<?php

namespace App\Repositories\Eloquent;

use App\Models\View;
use App\Repositories\Interfaces\ViewRepositoryInterface;

/**
 * Class ViewRepository
 * Eloquent implementation of ViewRepositoryInterface.
 * (Thực thi Eloquent cho ViewRepositoryInterface)
 */
final class ViewRepository extends BaseRepository implements ViewRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return View::class;
    }
}
