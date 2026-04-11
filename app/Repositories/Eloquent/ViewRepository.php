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

    /**
     * Get recent viewed location IDs by user.
     * (Lấy danh sách ID địa điểm đã xem gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentLocationIds(int $userId, int $limit = 10): array
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNotNull('location_id')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('location_id')
            ->unique()
            ->all();
    }
}
