<?php

namespace App\Repositories\Interfaces;

use App\Models\Favorite;
use Illuminate\Pagination\LengthAwarePaginator;

interface FavoriteRepositoryInterface extends RepositoryInterface
{
    public function getPaginatedByUser(int $userId, int $perPage): LengthAwarePaginator;

    /**
     * @return array{location_ids:int[], tour_ids:int[]}
     */
    public function getFavoriteIdsByUser(int $userId): array;

    public function findByUserAndLocation(int $userId, int $locationId): ?Favorite;

    public function findByUserAndTour(int $userId, int $tourId): ?Favorite;

    /**
     * @return int[]
     */
    public function getRecentLocationIds(int $userId, int $limit = 10): array;

    /**
     * @return int[]
     */
    public function getRecentTourIds(int $userId, int $limit = 10): array;
}
