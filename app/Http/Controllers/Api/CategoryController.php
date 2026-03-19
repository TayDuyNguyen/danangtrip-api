<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Validations\CategoryValidation;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CategoryController
 * Handles API requests for categories.
 * (Xử lý các yêu cầu API cho danh mục)
 */
final class CategoryController extends Controller
{
    /**
     * CategoryController constructor.
     * (Khởi tạo CategoryController)
     */
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Get all public categories (with subcategories).
     * (Lấy danh sách tất cả danh mục public kèm danh mục con)
     */
    public function index(): JsonResponse
    {
        $result = $this->categoryService->getPublicCategories();

        if ($result['status'] === 200) {
            return $this->success(['categories' => $result['data']]);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Get public category detail by id.
     * (Lấy chi tiết danh mục public theo id)
     */
    public function show(int $id): JsonResponse
    {
        $validator = CategoryValidation::validateShow($id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->getPublicCategoryById($id);

        if ($result['status'] === 200) {
            return $this->success(['category' => $result['data']]);
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Create a new category (admin).
     * (Tạo danh mục mới - admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = CategoryValidation::validateStore($request);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->createCategory($validator->validated());

        if ($result['status'] === 201) {
            return $this->created(['category' => $result['data']], 'Category created successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Update a category (admin).
     * (Cập nhật danh mục - admin)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = CategoryValidation::validateUpdate($request, $id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->updateCategory($id, $validator->validated());

        if ($result['status'] === 200) {
            return $this->success(['category' => $result['data']], 'Category updated successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a category (admin).
     * (Xóa danh mục - admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = CategoryValidation::validateDelete($id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->categoryService->deleteCategory($id);

        if ($result['status'] === 200) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
