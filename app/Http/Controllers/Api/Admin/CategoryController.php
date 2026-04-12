<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\DeleteCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\UpdateStatusCategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

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
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $result = $this->categoryService->createCategory($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['category' => $result['data']], 'Category created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing category.
     * (Cập nhật danh mục)
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->categoryService->updateCategory($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['category' => $result['data']], 'Category updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a category.
     * (Xóa danh mục)
     */
    public function destroy(DeleteCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->categoryService->deleteCategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update the status of a category.
     * (Cập nhật trạng thái danh mục)
     */
    public function updateStatus(UpdateStatusCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->categoryService->updateCategoryStatus($id, $request->validated('status'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
