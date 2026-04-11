<?php

namespace App\Repositories\Interfaces;

use App\Models\Favorite;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface FavoriteRepositoryInterface.
 * (Giao diện Repository cho Yêu thích)
 */
interface FavoriteRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated list of favorites for a user.
     * (Lấy danh sách yêu thích có phân trang cho người dùng)
     */
    public function getPaginatedByUser(int $userId, int $perPage): LengthAwarePaginator;

    /**
     * Find a favorite by user and location.
     * (Tìm yêu thích theo người dùng và địa điểm)
     */
    public function findByUserAndLocation(int $userId, int $locationId): ?Favorite;

    /**
     * Get recent favorited location IDs by user.
     * (Lấy danh sách ID địa điểm yêu thích gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentLocationIds(int $userId, int $limit = 10): array;
}
