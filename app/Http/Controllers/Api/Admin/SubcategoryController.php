<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subcategory\DeleteSubcategoryRequest;
use App\Http\Requests\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Subcategory\UpdateStatusSubcategoryRequest;
use App\Http\Requests\Subcategory\UpdateSubcategoryRequest;
use App\Services\SubcategoryService;
use Illuminate\Http\JsonResponse;

/**
 * Class SubcategoryController
 * Handles administrative API requests for subcategories.
 * (Xử lý các yêu cầu API quản trị cho danh mục con)
 */
final class SubcategoryController extends Controller
{
    public function __construct(
        protected SubcategoryService $subcategoryService
    ) {}

    /**
     * Create a new subcategory.
     * (Tạo danh mục con mới)
     */
    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $result = $this->subcategoryService->createSubcategory($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['subcategory' => $result['data']], 'Subcategory created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a subcategory.
     * (Cập nhật danh mục con)
     */
    public function update(UpdateSubcategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->subcategoryService->updateSubcategory($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['subcategory' => $result['data']], 'Subcategory updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a subcategory.
     * (Xóa danh mục con)
     */
    public function destroy(DeleteSubcategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->subcategoryService->deleteSubcategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update the status of a subcategory.
     * (Cập nhật trạng thái danh mục con)
     */
    public function updateStatus(UpdateStatusSubcategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->subcategoryService->updateSubcategoryStatus($id, $request->validated('status'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
