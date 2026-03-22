<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Validations\SubcategoryValidation;
use App\Services\SubcategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class SubcategoryController
 * Handles API requests for subcategories.
 * (Xử lý các yêu cầu API cho danh mục con)
 */
final class SubcategoryController extends Controller
{
    /**
     * SubcategoryController constructor.
     * (Khởi tạo SubcategoryController)
     */
    public function __construct(
        protected SubcategoryService $subcategoryService
    ) {}

    /**
     * Create a new subcategory (admin).
     * (Tạo danh mục con mới - admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = SubcategoryValidation::validateStore($request);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->createSubcategory($validator->validated());

        if ($result['status'] === 201) {
            return $this->created(['subcategory' => $result['data']], 'Subcategory created successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Update a subcategory (admin).
     * (Cập nhật danh mục con - admin)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = SubcategoryValidation::validateUpdate($request, $id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->updateSubcategory($id, $validator->validated());

        if ($result['status'] === 200) {
            return $this->success(['subcategory' => $result['data']], 'Subcategory updated successfully');
        }

        return $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a subcategory (admin).
     * (Xóa danh mục con - admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = SubcategoryValidation::validateDelete($id);

        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->subcategoryService->deleteSubcategory($id);

        if ($result['status'] === 200) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
