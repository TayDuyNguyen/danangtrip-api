<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use App\Repositories\Interfaces\BlogPostRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class BlogService
 * (Dịch vụ xử lý các hoạt động Blog)
 */
final class BlogService
{
    /**
     * BlogService constructor.
     * (Khởi tạo BlogService)
     */
    public function __construct(
        protected BlogPostRepositoryInterface $blogPostRepository,
        protected BlogCategoryRepositoryInterface $blogCategoryRepository
    ) {}

    /**
     * Get paginated blog posts for public view.
     * (Lấy danh sách bài viết Blog có phân trang cho chế độ công khai)
     */
    public function getPublicPosts(array $filters): array
    {
        try {
            $posts = $this->blogPostRepository->getPublicPosts($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $posts,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve blog posts.',
            ];
        }
    }

    /**
     * Get a single public blog post by slug.
     * (Lấy chi tiết một bài viết Blog theo slug cho chế độ công khai)
     */
    public function getPublicPostBySlug(string $slug): array
    {
        try {
            $post = $this->blogPostRepository->findPublicBySlug($slug);

            if (! $post) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog post not found.',
                ];
            }

            // Increment view count
            $this->blogPostRepository->incrementViewCount($post->id);
            $post->refresh();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $post,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve blog post details.',
            ];
        }
    }

    /**
     * Get all blog categories.
     * (Lấy tất cả danh mục Blog)
     */
    public function getBlogCategories(): array
    {
        try {
            $categories = $this->blogCategoryRepository->getAllCategories();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $categories,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve blog categories.',
            ];
        }
    }

    /**
     * Create a new blog post (Admin).
     * (Tạo bài viết Blog mới - Admin)
     */
    public function createBlogPost(array $data, int $authorId): array
    {
        return DB::transaction(function () use ($data, $authorId) {
            try {
                $data['author_id'] = $authorId;
                $data['slug'] = Str::slug($data['title']);

                // Ensure slug is unique
                $originalSlug = $data['slug'];
                $count = 1;
                while ($this->blogPostRepository->findOneBy(['slug' => $data['slug']])) {
                    $data['slug'] = $originalSlug.'-'.$count++;
                }

                if (! empty($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }

                $post = $this->blogPostRepository->create($data);

                if (! empty($data['category_ids'])) {
                    $this->blogPostRepository->syncCategories($post->id, $data['category_ids']);
                }

                return [
                    'status' => HttpStatusCode::CREATED->value,
                    'data' => $post->load('categories'),
                ];
            } catch (Exception $e) {
                Log::error($e);

                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to create blog post.',
                ];
            }
        });
    }

    /**
     * Update an existing blog post (Admin).
     * (Cập nhật bài viết Blog - Admin)
     */
    public function updateBlogPost(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            try {
                $post = $this->blogPostRepository->find($id);

                if (! $post) {
                    return [
                        'status' => HttpStatusCode::NOT_FOUND->value,
                        'message' => 'Blog post not found.',
                    ];
                }

                if (! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);

                    // Ensure slug is unique if title changed
                    if ($data['slug'] !== $post->slug) {
                        $originalSlug = $data['slug'];
                        $count = 1;
                        while ($this->blogPostRepository->findOneBy(['slug' => $data['slug']])) {
                            $data['slug'] = $originalSlug.'-'.$count++;
                        }
                    }
                }

                if (! empty($data['status']) && $data['status'] === 'published' && empty($post->published_at) && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }

                $this->blogPostRepository->update($id, $data);

                if (isset($data['category_ids'])) {
                    $this->blogPostRepository->syncCategories($id, $data['category_ids']);
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $post->fresh('categories'),
                ];
            } catch (Exception $e) {
                Log::error($e);

                return [
                    'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                    'message' => 'Failed to update blog post.',
                ];
            }
        });
    }

    /**
     * Delete a blog post (Admin).
     * (Xóa bài viết Blog - Admin)
     */
    public function deleteBlogPost(int $id): array
    {
        try {
            $post = $this->blogPostRepository->find($id);

            if (! $post) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog post not found.',
                ];
            }

            $this->blogPostRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Blog post deleted successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete blog post.',
            ];
        }
    }

    /**
     * Publish/Unpublish a blog post (Admin).
     * (Xuất bản/ẩn bài viết Blog - Admin)
     */
    public function togglePublishStatus(int $id, string $status): array
    {
        try {
            $post = $this->blogPostRepository->find($id);

            if (! $post) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog post not found.',
                ];
            }

            $updateData = [
                'status' => $status,
            ];

            if ($status === 'published' && empty($post->published_at)) {
                $updateData['published_at'] = now();
            }

            $this->blogPostRepository->update($id, $updateData);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $post->fresh(),
                'message' => 'Blog post status updated successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update blog post status.',
            ];
        }
    }
}
