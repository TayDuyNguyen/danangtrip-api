<?php

namespace App\Repositories\Interfaces;

/**
 * Interface ViewRepositoryInterface
 * Define standard operations for View repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Lượt xem)
 */
interface ViewRepositoryInterface extends RepositoryInterface
{
    /**
     * Get recent viewed location IDs by user.
     * (Lấy danh sách ID địa điểm đã xem gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentLocationIds(int $userId, int $limit = 10): array;

    /**
     * Get recent viewed tour IDs by user.
     * (Lấy danh sách ID tour đã xem gần đây của người dùng)
     *
     * @return int[]
     */
    public function getRecentTourIds(int $userId, int $limit = 10): array;
}
