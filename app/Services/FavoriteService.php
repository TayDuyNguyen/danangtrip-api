<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Exception;

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
            $idsOnly = filter_var($params['ids_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($idsOnly) {
                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->favoriteRepository->getFavoriteIdsByUser($userId),
                ];
            }

            $perPage = $params['per_page'] ?? Pagination::PER_PAGE->value;
            $favorites = $this->favoriteRepository->getPaginatedByUser($userId, (int) $perPage);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $favorites,
            ];
        } catch (Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve favorites.',
            ];
        }
    }

    /**
     * Save a location or tour to user's favorites.
     * (Lưu một địa điểm hoặc tour vào danh sách yêu thích của người dùng)
     */
    public function saveFavorite(int $userId, ?int $locationId = null, ?int $tourId = null): array
    {
        try {
            $existing = null;
            if ($locationId) {
                $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);
            } elseif ($tourId) {
                $existing = $this->favoriteRepository->findByUserAndTour($userId, $tourId);
            }

            if ($existing) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'This item is already in your favorites.',
                ];
            }

            $this->favoriteRepository->create([
                'user_id' => $userId,
                'location_id' => $locationId,
                'tour_id' => $tourId,
            ]);

            if ($locationId) {
                $this->locationRepository->increment($locationId, 'favorite_count');
            }

            return [
                'status' => HttpStatusCode::CREATED->value,
                'message' => 'Added to favorites successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to add to favorites.',
            ];
        }
    }

    /**
     * Remove a location or tour from user's favorites.
     * (Xóa địa điểm hoặc tour khỏi danh sách yêu thích của người dùng)
     */
    public function unsaveFavorite(int $userId, ?int $locationId = null, ?int $tourId = null): array
    {
        try {
            $existing = null;
            if ($locationId) {
                $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);
            } elseif ($tourId) {
                $existing = $this->favoriteRepository->findByUserAndTour($userId, $tourId);
            }

            if (! $existing) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Favorite record not found.',
                ];
            }

            $this->favoriteRepository->delete($existing->id);
            if ($locationId) {
                $this->locationRepository->decrement($locationId, 'favorite_count', 1, [['favorite_count', '>', 0]]);
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Removed from favorites successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to remove from favorites.',
            ];
        }
    }

    /**
     * Check if a location or tour is favorited by a user.
     * (Kiểm tra xem địa điểm hoặc tour đã được người dùng yêu thích chưa)
     */
    public function checkFavorite(int $userId, ?int $locationId = null, ?int $tourId = null): array
    {
        try {
            $existing = null;
            if ($locationId) {
                $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);
            } elseif ($tourId) {
                $existing = $this->favoriteRepository->findByUserAndTour($userId, $tourId);
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'is_favorite' => (bool) $existing,
                ],
            ];
        } catch (Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to check favorite status.',
            ];
        }
    }
}
