<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\SubcategoryValidation;
use App\Services\SubcategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function store(Request $request): JsonResponse
    {
        $validator = SubcategoryValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->createSubcategory($validator->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['subcategory' => $result['data']], 'Subcategory created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a subcategory.
     * (Cập nhật danh mục con)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = SubcategoryValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->updateSubcategory($id, $validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['subcategory' => $result['data']], 'Subcategory updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a subcategory.
     * (Xóa danh mục con)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = SubcategoryValidation::validateDelete($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->deleteSubcategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update the status of a subcategory.
     * (Cập nhật trạng thái danh mục con)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = SubcategoryValidation::validateUpdateStatus($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->updateSubcategoryStatus($id, $validator->validated()['status']);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
