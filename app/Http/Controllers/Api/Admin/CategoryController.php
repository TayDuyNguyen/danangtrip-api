<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Validations\CategoryValidation;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CategoryController
 * Handles administrative API requests for categories.
 * (Xử lý các yêu cầu API quản trị cho danh mục)
 */
final class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Create a new category.
     * (Tạo danh mục mới)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = CategoryValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->createCategory($validator->validated());

        return $result['status'] === 201
            ? $this->created(['category' => $result['data']], 'Category created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a category.
     * (Cập nhật danh mục)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = CategoryValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->updateCategory($id, $validator->validated());

        return $result['status'] === 200
            ? $this->success(['category' => $result['data']], 'Category updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a category.
     * (Xóa danh mục)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = CategoryValidation::validateDelete($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->deleteCategory($id);

        return $result['status'] === 200
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
