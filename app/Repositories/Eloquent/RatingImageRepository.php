<?php

namespace App\Repositories\Eloquent;

use App\Models\RatingImage;
use App\Repositories\Interfaces\RatingImageRepositoryInterface;

/**
 * Class RatingImageRepository
 * Eloquent implementation of RatingImageRepositoryInterface.
 * (Thực thi Eloquent cho RatingImageRepositoryInterface)
 */
final class RatingImageRepository extends BaseRepository implements RatingImageRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return RatingImage::class;
    }

    /**
     * Delete all images by rating id.
     * (Xóa tất cả ảnh theo rating id)
     */
    public function deleteByRatingId(int $ratingId): int
    {
        return $this->model->newQuery()->where('rating_id', $ratingId)->delete();
    }

    /**
     * Create multiple rating images.
     * (Tạo nhiều ảnh đánh giá)
     *
     * @param  string[]  $imageUrls
     */
    public function createMany(int $ratingId, array $imageUrls): bool
    {
        $rows = [];
        foreach (array_values($imageUrls) as $index => $url) {
            $rows[] = [
                'rating_id' => $ratingId,
                'image_url' => (string) $url,
                'sort_order' => $index,
                'created_at' => now(),
            ];
        }

        if (count($rows) === 0) {
            return true;
        }

        return (bool) $this->insert($rows);
    }
}
