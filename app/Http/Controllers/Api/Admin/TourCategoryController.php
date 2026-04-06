<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\TourCategoryValidation;
use App\Services\TourCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TourCategoryController
 * Handles admin API requests for tour category management.
 * (Xử lý các yêu cầu API admin cho quản lý danh mục tour)
 */
final class TourCategoryController extends Controller
{
    /**
     * TourCategoryController constructor.
     * (Khởi tạo TourCategoryController)
     */
    public function __construct(
        protected TourCategoryService $tourCategoryService
    ) {}

    /**
     * Display a listing of tour categories (Admin).
     * (Danh sách danh mục tour - Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->tourCategoryService->getCategories($request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Store a new tour category.
     * (Tạo danh mục tour mới)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = TourCategoryValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourCategoryService->createCategory($validator->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['category' => $result['data']], 'Tour category created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing tour category.
     * (Cập nhật danh mục tour)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = TourCategoryValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourCategoryService->updateCategory($id, $validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['category' => $result['data']], 'Tour category updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a tour category.
     * (Xóa danh mục tour)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = TourCategoryValidation::validateShow($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourCategoryService->deleteCategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update tour category status.
     * (Cập nhật trạng thái danh mục tour)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = TourCategoryValidation::validateUpdateStatus($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->tourCategoryService->updateStatus($id, $request->status);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
