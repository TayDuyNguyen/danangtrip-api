<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Repositories\Interfaces\FavoriteRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class FavoriteService
 * (Dịch vụ xử lý Logic liên quan đến Yêu thích)
 */
class FavoriteService
{
    protected $favoriteRepository;

    protected $locationRepository;

    public function __construct(
        FavoriteRepositoryInterface $favoriteRepository,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->favoriteRepository = $favoriteRepository;
        $this->locationRepository = $locationRepository;
    }

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
                'message' => 'Failed to retrieve favorites',
            ];
        }
    }

    public function toggleFavorite(int $userId, int $locationId): array
    {
        try {
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);

            if ($existing) {
                $this->favoriteRepository->delete($existing->id);
                $this->locationRepository->decrementFavoriteCount($locationId);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Location removed from favorites',
                    'is_favorite' => false,
                ];
            } else {
                $this->favoriteRepository->create([
                    'user_id' => $userId,
                    'location_id' => $locationId,
                ]);
                $this->locationRepository->incrementFavoriteCount($locationId);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Location added to favorites',
                    'is_favorite' => true,
                ];
            }
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Something went wrong while toggling favorite',
            ];
        }
    }

    public function checkFavorite(int $userId, int $locationId): array
    {
        try {
            $existing = $this->favoriteRepository->findByUserAndLocation($userId, $locationId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'is_favorite' => (bool) $existing,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to check favorite status',
            ];
        }
    }
}
