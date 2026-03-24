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
     *
     * @return void
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

            if ($category->subcategories()->exists()) {
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
}
