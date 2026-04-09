<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\TourCategoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class TourCategoryService
 * Handles business logic related to tour categories.
 * (Xử lý logic nghiệp vụ liên quan đến danh mục tour)
 */
final class TourCategoryService
{
    /**
     * TourCategoryService constructor.
     * (Khởi tạo TourCategoryService)
     */
    public function __construct(
        protected TourCategoryRepositoryInterface $tourCategoryRepository
    ) {}

    /**
     * Get active tour categories.
     * (Lấy danh sách danh mục tour đang hoạt động)
     */
    public function getActiveCategories(): array
    {
        try {
            $categories = $this->tourCategoryRepository->getActiveCategories();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $categories,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tour categories',
            ];
        }
    }

    /**
     * Get tours by category slug.
     * (Lấy danh sách tour theo slug danh mục)
     */
    public function getToursBySlug(string $slug, array $filters = []): array
    {
        try {
            $tours = $this->tourCategoryRepository->getToursBySlug($slug, $filters);
            if (! $tours) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $tours,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get tours by category',
            ];
        }
    }

    /**
     * Get categories (Admin).
     * (Lấy danh sách danh mục - Admin)
     */
    public function getCategories(array $filters = []): array
    {
        try {
            $categories = $this->tourCategoryRepository->getCategories($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $categories,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get categories',
            ];
        }
    }

    /**
     * Create a new tour category.
     * (Tạo danh mục tour mới)
     */
    public function createCategory(array $data): array
    {
        try {
            if (empty($data['slug'])) {
                $data['slug'] = $this->tourCategoryRepository->generateUniqueSlug($data['name']);
            }
            $category = $this->tourCategoryRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $category,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create tour category',
            ];
        }
    }

    /**
     * Update an existing tour category.
     * (Cập nhật danh mục tour)
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->tourCategoryRepository->generateUniqueSlug($data['name'], 'slug', $id);
            }
            $updated = $this->tourCategoryRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->tourCategoryRepository->find($id),
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update tour category',
            ];
        }
    }

    /**
     * Delete a tour category.
     * (Xóa danh mục tour)
     */
    public function deleteCategory(int $id): array
    {
        try {
            if ($this->tourCategoryRepository->hasTours($id)) {
                return [
                    'status' => HttpStatusCode::BAD_REQUEST->value,
                    'message' => 'Cannot delete category with associated tours',
                ];
            }
            $deleted = $this->tourCategoryRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Tour category deleted successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete tour category',
            ];
        }
    }

    /**
     * Update tour category status.
     * (Cập nhật trạng thái danh mục tour)
     */
    public function updateStatus(int $id, string $status): array
    {
        try {
            $updated = $this->tourCategoryRepository->updateStatus($id, $status);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Tour category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Status updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update status',
            ];
        }
    }
}
