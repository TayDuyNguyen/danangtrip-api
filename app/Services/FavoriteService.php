<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class FavoriteService.
 * (Lớp dịch vụ cho Yêu thích)
 */
final class FavoriteService
{
    /**
     * FavoriteService constructor.
     * (Khởi tạo FavoriteService)
     */
    public function __construct(
        protected FavoriteRepositoryInterface $favoriteRepository,
        protected LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Get list of favorites for a user.
     * (Lấy danh sách yêu thích của người dùng)
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
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'An error occurred while fetching the favorites list.',
            ];
        }
    }

    /**
     * Add a location to favorites.
     * (Thêm địa điểm vào danh sách yêu thích)
     */
    public function saveFavorite(int $userId, int $locationId): array
    {
        DB::beginTransaction();
        try {
            // Check if already favorited
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);
            if ($existing) {
                DB::rollBack();

                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'This location is already in your favorites list.',
                ];
            }

            // Create favorite
            $this->favoriteRepository->create([
                'user_id' => $userId,
                'location_id' => $locationId,
                'created_at' => now(),
            ]);

            // Increment favorite_count in location
            $this->locationRepository->incrementFavoriteCount($locationId);

            DB::commit();

            return [
                'status' => HttpStatusCode::CREATED->value,
                'message' => 'Added to favorites list.',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'An error occurred while adding to the favorites list.',
            ];
        }
    }

    /**
     * Remove a location from favorites.
     * (Xóa địa điểm khỏi danh sách yêu thích)
     */
    public function unsaveFavorite(int $userId, int $locationId): array
    {
        DB::beginTransaction();
        try {
            $favorite = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);
            if (! $favorite) {
                DB::rollBack();

                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Location not found in your favorites list.',
                ];
            }

            // Delete favorite
            $this->favoriteRepository->delete($favorite->id);

            // Decrement favorite_count in location
            $this->locationRepository->decrementFavoriteCount($locationId);

            DB::commit();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Removed from favorites list.',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'An error occurred while removing from the favorites list.',
            ];
        }
    }
}
