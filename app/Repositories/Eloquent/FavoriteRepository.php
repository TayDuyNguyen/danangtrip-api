<?php

namespace App\Repositories\Eloquent;

use App\Models\Favorite;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class FavoriteRepository.
 * (Lớp triển khai Repository cho Yêu thích bằng Eloquent)
 */
final class FavoriteRepository extends BaseRepository implements FavoriteRepositoryInterface
{
    /**
     * Specify model class name.
     * (Chỉ định tên lớp Model)
     */
    public function getModel(): string
    {
        return Favorite::class;
    }

    /**
     * Get paginated list of favorites for a user.
     * (Lấy danh sách yêu thích có phân trang cho người dùng)
     */
    public function getPaginatedByUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->with(['location' => function ($query) {
                $query->with(['category']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a favorite by user and location.
     * (Tìm yêu thích theo người dùng và địa điểm)
     */
    public function findByUserAndLocation(int $userId, int $locationId): ?Favorite
    {
        return $this->model->where('user_id', $userId)
            ->where('location_id', $locationId)
            ->first();
    }
}
