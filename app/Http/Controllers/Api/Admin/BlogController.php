<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\BlogValidation;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function store(Request $request): JsonResponse
    {
        $validator = BlogValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $authorId = auth('api')->id();
        $result = $this->blogService->createBlogPost($validator->validated(), $authorId);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], 'Blog post created successfully.')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update an existing blog post.
     * (Cập nhật bài viết Blog)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = BlogValidation::validateUpdate($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->blogService->updateBlogPost($id, $validator->validated());

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
    public function publish(Request $request, int $id): JsonResponse
    {
        $validator = BlogValidation::validatePublish($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->blogService->togglePublishStatus($id, $request->input('status'));

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
