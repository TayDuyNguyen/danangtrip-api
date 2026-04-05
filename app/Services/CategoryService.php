<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\CategoryRepositoryInterface;

/**
 * Class CategoryService
 * Handles business logic related to categories.
 * (Xử lý logic nghiệp vụ liên quan đến danh mục)
 */
final class CategoryService
{
    /**
     * CategoryService constructor.
     * (Khởi tạo CategoryService)
     */
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get public categories (active) with active subcategories.
     * (Lấy danh sách danh mục public đang hoạt động kèm danh mục con đang hoạt động)
     */
    public function getPublicCategories(): array
    {
        try {
            $categories = $this->categoryRepository->getPublicCategories();

            if ($categories->isEmpty()) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Categories not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $categories,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get categories',
            ];
        }
    }

    /**
     * Get a public category by id (active) with active subcategories.
     * (Lấy chi tiết danh mục public theo id kèm danh mục con đang hoạt động)
     */
    public function getPublicCategoryById(int $id): array
    {
        try {
            $category = $this->categoryRepository->getPublicCategoryById($id);

            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $category,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get category',
            ];
        }
    }

    /**
     * Create a new category.
     * (Tạo danh mục mới)
     */
    public function createCategory(array $data): array
    {
        try {
            if (array_key_exists('slug', $data) && ! empty($data['slug'])) {
                $data['slug'] = $this->categoryRepository->generateUniqueSlug($data['slug']);
            } elseif (! array_key_exists('slug', $data) && array_key_exists('name', $data) && ! empty($data['name'])) {
                $data['slug'] = $this->categoryRepository->generateUniqueSlug($data['name']);
            }

            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['status'] = $data['status'] ?? 'active';

            $category = $this->categoryRepository->create($data);

            if (! $category) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to create category',
                ];
            }

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $category,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create category',
            ];
        }
    }

    /**
     * Update an existing category.
     * (Cập nhật danh mục)
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            $category = $this->categoryRepository->find($id);
            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            if (array_key_exists('slug', $data) && ! empty($data['slug'])) {
                $data['slug'] = $this->categoryRepository->generateUniqueSlug($data['slug'], 'slug', $id);
            } elseif (! array_key_exists('slug', $data) && array_key_exists('name', $data) && ! empty($data['name'])) {
                $data['slug'] = $this->categoryRepository->generateUniqueSlug($data['name'], 'slug', $id);
            }

            $updated = $this->categoryRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update category',
                ];
            }

            $category = $this->categoryRepository->find($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $category,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update category',
            ];
        }
    }

    /**
     * Delete a category.
     * (Xóa danh mục)
     */
    public function deleteCategory(int $id): array
    {
        try {
            $category = $this->categoryRepository->find($id);
            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            if ($this->categoryRepository->hasSubcategories($id)) {
                return [
                    'status' => HttpStatusCode::CONFLICT->value,
                    'message' => 'Cannot delete category because it has subcategories',
                ];
            }

            $deleted = $this->categoryRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Category deleted successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete category',
            ];
        }
    }

    /**
     * Get paginated active locations for a category by its slug.
     * (Lấy danh sách địa điểm của danh mục theo slug, có phân trang)
     */
    public function getLocationsByCategorySlug(string $slug, int $perPage): array
    {
        try {
            $paginator = $this->categoryRepository->getLocationsBySlug($slug, $perPage);

            if (! $paginator) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $paginator,
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get locations for category',
            ];
        }
    }

    /**
     * Update the status of a category.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateCategoryStatus(int $id, string $status): array
    {
        try {
            $category = $this->categoryRepository->find($id);
            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Category not found',
                ];
            }

            $this->categoryRepository->updateStatus($id, $status);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Category status updated successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update category status',
            ];
        }
    }
}
