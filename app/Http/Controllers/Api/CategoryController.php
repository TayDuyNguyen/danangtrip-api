<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Enums\Pagination;
use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

/**
 * Class CategoryController
 * (Controller xử lý các yêu cầu liên quan đến Danh mục)
 */
class CategoryController extends Controller
{
    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * CategoryController constructor.
     */
    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of public categories.
     * (Hiển thị danh sách các danh mục công khai)
     */
    public function index(): JsonResponse
    {
        $result = $this->categoryService->getPublicCategories();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified public category.
     * (Hiển thị danh mục công khai cụ thể)
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->categoryService->getPublicCategoryById($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get locations by category slug with pagination.
     * (Lấy danh sách địa điểm theo slug Danh mục kèm phân trang)
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
