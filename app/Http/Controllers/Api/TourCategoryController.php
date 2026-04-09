<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\TourCategory\ToursBySlugTourCategoryRequest;
use App\Services\TourCategoryService;
use Illuminate\Http\JsonResponse;

/**
 * Class TourCategoryController
 * Handles public API requests for tour categories.
 * (Xử lý các yêu cầu API công khai cho danh mục tour)
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
     * Display a listing of active tour categories.
     * (Danh sách danh mục tour đang hoạt động)
     */
    public function index(): JsonResponse
    {
        $result = $this->tourCategoryService->getActiveCategories();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display paginated tours by category slug.
     * (Danh sách tour theo slug danh mục, có phân trang)
     */
    public function toursBySlug(ToursBySlugTourCategoryRequest $request, string $slug): JsonResponse
    {
        $result = $this->tourCategoryService->getToursBySlug($slug, $request->all());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
