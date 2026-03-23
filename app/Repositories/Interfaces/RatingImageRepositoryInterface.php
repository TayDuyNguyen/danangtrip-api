<?php

namespace App\Repositories\Interfaces;

/**
 * Interface RatingImageRepositoryInterface
 * Define standard operations for RatingImage repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Ảnh đánh giá)
 */
interface RatingImageRepositoryInterface extends RepositoryInterface
{
    /**
     * Delete all images by rating id.
     * (Xóa tất cả ảnh theo rating id)
     */
    public function deleteByRatingId(int $ratingId): int;

    /**
     * Create multiple rating images.
     * (Tạo nhiều ảnh đánh giá)
     *
     * @param  string[]  $imageUrls
     */
    public function createMany(int $ratingId, array $imageUrls): bool;
}
