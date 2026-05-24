<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\LocationsBySlugCategoryRequest;
use App\Http\Requests\Category\ShowCategoryRequest;
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
    public function index(IndexCategoryRequest $request): JsonResponse
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
    public function show(ShowCategoryRequest $request, int $id): JsonResponse
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
    public function locationsBySlug(LocationsBySlugCategoryRequest $request, string $slug): JsonResponse
    {
        $result = $this->categoryService->getLocationsByCategorySlug($slug, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
