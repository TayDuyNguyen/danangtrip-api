<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class FavoriteService.
 * (Dịch vụ xử lý Logic liên quan đến Yêu thích)
 */
class FavoriteService
{
    protected FavoriteRepositoryInterface $favoriteRepository;

    protected LocationRepositoryInterface $locationRepository;

    /**
     * FavoriteService constructor.
     */
    public function __construct(
        FavoriteRepositoryInterface $favoriteRepository,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->favoriteRepository = $favoriteRepository;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Get paginated lists of favorite locations for a user.
     * (Lấy danh sách địa điểm yêu thích có phân trang của người dùng)
     */
    public function getFavorites(int $userId, array $params): array
    {
        try {
            $perPage = $params['per_page'] ?? Pagination::PER_PAGE->value;
            $favorites = $this->favoriteRepository->getPaginatedByUser($userId, (int) $perPage);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $favorites,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve favorites.',
            ];
        }
    }

    /**
     * Save a location to user's favorites.
     * (Lưu một địa điểm vào danh sách yêu thích của người dùng)
     */
    public function saveFavorite(int $userId, int $locationId): array
    {
        try {
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);

            if ($existing) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'This location is already in your favorites.',
                ];
            }

            $this->favoriteRepository->create([
                'user_id' => $userId,
                'location_id' => $locationId,
            ]);

            $this->locationRepository->incrementFavoriteCount($locationId);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'message' => 'Location added to favorites successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to add location to favorites.',
            ];
        }
    }

    /**
     * Remove a location from user's favorites.
     * (Xóa địa điểm khỏi danh sách yêu thích của người dùng)
     */
    public function unsaveFavorite(int $userId, int $locationId): array
    {
        try {
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);

            if (! $existing) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Favorite record not found.',
                ];
            }

            $this->favoriteRepository->delete($existing->id);
            $this->locationRepository->decrementFavoriteCount($locationId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Location removed from favorites successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to remove location from favorites.',
            ];
        }
    }

    /**
     * Check if a location is favorited by a user.
     * (Kiểm tra xem địa điểm đã được người dùng yêu thích chưa)
     */
    public function checkFavorite(int $userId, int $locationId): array
    {
        try {
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'is_favorite' => (bool) $existing,
                ],
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to check favorite status.',
            ];
        }
    }
}
