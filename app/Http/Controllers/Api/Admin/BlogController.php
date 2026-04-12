<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\IndexAdminBlogRequest;
use App\Http\Requests\Blog\IndexBlogCategoryRequest;
use App\Http\Requests\Blog\StoreBlogCategoryRequest;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogCategoryRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Http\Requests\Blog\UpdateStatusBlogRequest;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;

/**
 * Class BlogController
 * (Điều khiển các hoạt động Blog cho Admin)
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
     * Store a new blog post.
     * (Tạo bài viết Blog mới)
     */
    public function store(StoreBlogRequest $request): JsonResponse
    {
        $authorId = auth('api')->id();
        $result = $this->blogService->createBlogPost($request->validated(), $authorId);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], 'Blog post created successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing blog post.
     * (Cập nhật bài viết Blog)
     */
    public function update(UpdateBlogRequest $request, int $id): JsonResponse
    {
        $result = $this->blogService->updateBlogPost($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Blog post updated successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Remove the specified blog post.
     * (Xóa bài viết Blog)
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->blogService->deleteBlogPost($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get paginated list of blog posts for admin (including drafts).
     * (Lấy danh sách bài viết blog có phân trang cho admin - bao gồm cả draft)
     */
    public function index(IndexAdminBlogRequest $request): JsonResponse
    {
        $result = $this->blogService->getAdminPosts($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Blog posts retrieved successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get a single blog post by ID for admin.
     * (Lấy chi tiết bài viết blog theo ID cho admin)
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->blogService->getAdminPostById($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Blog post retrieved successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update blog post status.
     * (Cập nhật trạng thái bài viết blog)
     */
    public function updateStatus(UpdateStatusBlogRequest $request, int $id): JsonResponse
    {
        $result = $this->blogService->updatePostStatus($id, $request->validated('status'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get all blog categories.
     * (Lấy tất cả danh mục blog)
     */
    public function indexCategories(IndexBlogCategoryRequest $request): JsonResponse
    {
        $result = $this->blogService->getBlogCategories();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Blog categories retrieved successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Store a new blog category.
     * (Tạo danh mục blog mới)
     */
    public function storeCategory(StoreBlogCategoryRequest $request): JsonResponse
    {
        $result = $this->blogService->createCategory($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], 'Blog category created successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing blog category.
     * (Cập nhật danh mục blog)
     */
    public function updateCategory(UpdateBlogCategoryRequest $request, int $id): JsonResponse
    {
        $result = $this->blogService->updateCategory($id, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], 'Blog category updated successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a blog category.
     * (Xóa danh mục blog)
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $result = $this->blogService->deleteCategory($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
