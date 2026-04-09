<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\PublishBlogRequest;
use App\Http\Requests\Blog\StoreBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
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
     * Publish/Unpublish a blog post.
     * (Xuất bản/ẩn bài viết Blog)
     */
    public function publish(PublishBlogRequest $request, int $id): JsonResponse
    {
        $result = $this->blogService->togglePublishStatus($id, $request->input('status'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
