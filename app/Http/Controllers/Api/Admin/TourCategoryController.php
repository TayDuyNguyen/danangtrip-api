<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\TourCategory\IndexTourCategoryRequest;
use App\Http\Requests\TourCategory\ShowTourCategoryRequest;
use App\Http\Requests\TourCategory\StoreTourCategoryRequest;
use App\Http\Requests\TourCategory\UpdateStatusTourCategoryRequest;
use App\Http\Requests\TourCategory\UpdateTourCategoryRequest;
use App\Services\TourCategoryService;
use Illuminate\Http\JsonResponse;

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
    public function index(IndexTourCategoryRequest $request): JsonResponse
    {
        $result = $this->tourCategoryService->getCategories($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Store a new tour category.
     * (Tạo danh mục tour mới)
     */
    public function store(StoreTourCategoryRequest $request): JsonResponse
    {
        $result = $this->tourCategoryService->createCategory($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created(['category' => $result['data']], 'Tour category created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing tour category.
     * (Cập nhật danh mục tour)
     */
    public function update(UpdateTourCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->tourCategoryService->updateCategory($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(['category' => $result['data']], 'Tour category updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a tour category.
     * (Xóa danh mục tour)
     */
    public function destroy(ShowTourCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->tourCategoryService->deleteCategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update tour category status.
     * (Cập nhật trạng thái danh mục tour)
     */
    public function updateStatus(UpdateStatusTourCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->tourCategoryService->updateStatus($id, $request->status);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
