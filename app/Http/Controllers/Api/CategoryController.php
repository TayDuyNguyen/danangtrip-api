<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Http\Controllers\Controller;
use App\Http\Validations\CategoryValidation;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

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

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
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

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get active locations for a specific category identified by slug.
     * (Lấy danh sách địa điểm theo slug danh mục)
     */
    public function locationsBySlug(string $slug): JsonResponse
    {
        $perPage = (int) request()->input('per_page', Pagination::PER_PAGE->value);
        $result = $this->categoryService->getLocationsByCategorySlug($slug, $perPage);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
