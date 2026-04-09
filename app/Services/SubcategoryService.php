<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\SubcategoryRepositoryInterface;
use Illuminate\Support\Facades\Log;

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
                'status' => HttpStatusCode::CREATED->value,
                'data' => $subcategory,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
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
                    'status' => HttpStatusCode::NOT_FOUND->value,
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
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update subcategory',
                ];
            }

            $subcategory = $this->subcategoryRepository->find($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $subcategory,
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
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
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Subcategory not found',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Subcategory deleted successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete subcategory',
            ];
        }
    }

    /**
     * Update the status of a subcategory.
     * (Cập nhật trạng thái danh mục con)
     */
    public function updateSubcategoryStatus(int $id, string $status): array
    {
        try {
            $subcategory = $this->subcategoryRepository->find($id);
            if (! $subcategory) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Subcategory not found',
                ];
            }

            $this->subcategoryRepository->updateStatus($id, $status);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Subcategory status updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update subcategory status',
            ];
        }
    }
}
