<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\IndexBlogRequest;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;

/**
 * Class BlogController
 * (Điều khiển các hoạt động Blog công khai)
 */
final class BlogController extends Controller
{
    /**
     * BlogController constructor.
     * (Khởi tạo BlogController)
     */
    public function __construct(
        protected BlogService $blogService
    ) {}

    /**
     * Display a listing of blog posts.
     * (Danh sách bài viết Blog)
     */
    public function index(IndexBlogRequest $request): JsonResponse
    {
        $result = $this->blogService->getPublicPosts($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display the specified blog post.
     * (Chi tiết bài viết Blog)
     */
    public function show(string $slug): JsonResponse
    {
        $result = $this->blogService->getPublicPostBySlug($slug);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Display a listing of blog categories.
     * (Danh sách danh mục Blog)
     */
    public function categories(): JsonResponse
    {
        $result = $this->blogService->getBlogCategories();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
