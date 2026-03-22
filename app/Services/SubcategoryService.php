<?php

namespace App\Services;

use App\Repositories\Interfaces\SubcategoryRepositoryInterface;

/**
 * Class SubcategoryService
 * Handles business logic related to subcategories.
 * (Xử lý logic nghiệp vụ liên quan đến danh mục con)
 */
final class SubcategoryService
{
    /**
     * SubcategoryService constructor.
     * (Khởi tạo SubcategoryService)
     */
    public function __construct(
        protected SubcategoryRepositoryInterface $subcategoryRepository
    ) {}

    /**
     * Create a new subcategory.
     * (Tạo danh mục con mới)
     */
    public function createSubcategory(array $data): array
    {
        try {
            if (! empty($data['slug'])) {
                $data['slug'] = $this->subcategoryRepository->generateUniqueSlug($data['slug']);
            } elseif (! empty($data['name'])) {
                $data['slug'] = $this->subcategoryRepository->generateUniqueSlug($data['name']);
            }

            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['status'] = $data['status'] ?? 'active';

            $subcategory = $this->subcategoryRepository->create($data);

            return [
                'status' => 201,
                'data' => $subcategory,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to create subcategory',
            ];
        }
    }

    /**
     * Update an existing subcategory.
     * (Cập nhật danh mục con)
     */
    public function updateSubcategory(int $id, array $data): array
    {
        try {
            $subcategory = $this->subcategoryRepository->find($id);
            if (! $subcategory) {
                return [
                    'status' => 404,
                    'message' => 'Subcategory not found',
                ];
            }

            if (array_key_exists('slug', $data) && ! empty($data['slug'])) {
                $data['slug'] = $this->subcategoryRepository->generateUniqueSlug($data['slug'], 'slug', $id);
            } elseif (! array_key_exists('slug', $data) && array_key_exists('name', $data) && ! empty($data['name'])) {
                $data['slug'] = $this->subcategoryRepository->generateUniqueSlug($data['name'], 'slug', $id);
            }

            $updated = $this->subcategoryRepository->update($id, $data);
            if (! $updated) {
                return [
                    'status' => 500,
                    'message' => 'Failed to update subcategory',
                ];
            }

            $subcategory = $this->subcategoryRepository->find($id);

            return [
                'status' => 200,
                'data' => $subcategory,
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to update subcategory',
            ];
        }
    }

    /**
     * Delete a subcategory.
     * (Xóa danh mục con)
     */
    public function deleteSubcategory(int $id): array
    {
        try {
            $deleted = $this->subcategoryRepository->delete($id);
            if (! $deleted) {
                return [
                    'status' => 404,
                    'message' => 'Subcategory not found',
                ];
            }

            return [
                'status' => 200,
                'message' => 'Subcategory deleted successfully',
            ];
        } catch (\Exception $_) {
            return [
                'status' => 500,
                'message' => 'Failed to delete subcategory',
            ];
        }
    }
}
