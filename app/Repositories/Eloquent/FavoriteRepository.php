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
        return $this->model->newQuery()
            ->where('user_id', $userId)
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
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('location_id', $locationId)
            ->first();
    }

    /**
     * Get recent favorited location IDs by user.
     * (Lấy danh sách ID địa điểm yêu thích gần đây của người dùng)
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

    /**
     * Get recent favorited tour IDs by user.
     * (Lấy danh sách ID tour yêu thích gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentTourIds(int $userId, int $limit = 10): array
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNotNull('tour_id')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('tour_id')
            ->unique()
            ->all();
    }
}
