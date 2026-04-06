<?php

namespace App\Repositories\Eloquent;

use App\Models\TourCategory;
use App\Repositories\Interfaces\TourCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TourCategoryRepository
 * Eloquent implementation of TourCategoryRepositoryInterface.
 * (Triển khai Eloquent cho TourCategoryRepositoryInterface)
 */
class TourCategoryRepository extends BaseRepository implements TourCategoryRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return TourCategory::class;
    }

    /**
     * Get active tour categories.
     * (Lấy danh sách danh mục tour đang hoạt động)
     */
    public function getActiveCategories(): Collection
    {
        return $this->model->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get paginated tours by category slug.
     * (Lấy danh sách tour theo slug danh mục, có phân trang)
     */
    public function getToursBySlug(string $slug, array $filters = []): ?LengthAwarePaginator
    {
        $category = $this->model->where('slug', $slug)->where('status', 'active')->first();

        if (! ($category instanceof TourCategory)) {
            return null;
        }

        $perPage = $filters['per_page'] ?? 10;

        return $category->tours()
            ->where('status', 'active')
            ->orderBy($filters['sort'] ?? 'created_at', $filters['order'] ?? 'desc')
            ->paginate($perPage);
    }

    /**
     * Get categories with optional filters (Admin).
     * (Lấy danh sách danh mục với các bộ lọc tùy chọn - Admin)
     */
    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->orderBy('sort_order')->paginate($perPage);
    }

    /**
     * Update category status.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(int $id, string $status): bool
    {
        $category = $this->find($id);
        if (! $category) {
            return false;
        }

        return $category->update(['status' => $status]);
    }

    /**
     * Check if category has any tours.
     * (Kiểm tra xem danh mục có bất kỳ tour nào không)
     */
    public function hasTours(int $id): bool
    {
        $category = $this->find($id);
        if (! $category) {
            return false;
        }

        return $category->tours()->exists();
    }
}
