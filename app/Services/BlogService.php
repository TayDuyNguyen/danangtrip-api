<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\BlogCategoryRepositoryInterface;
use App\Repositories\Interfaces\BlogPostRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
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
        $data['author_id'] = $authorId;
        $data['slug'] = Str::slug($data['title']);

        try {
            $post = DB::transaction(function () use ($data) {
                // Ensure slug is unique
                $originalSlug = $data['slug'];
                $count = 1;
                while ($this->blogPostRepository->firstWhere(['slug' => $data['slug']])) {
                    $data['slug'] = $originalSlug.'-'.$count++;
                }

                if (! empty($data['status']) && $data['status'] === 'published' && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }

                $post = $this->blogPostRepository->create($data);

                if (! empty($data['category_ids'])) {
                    $this->blogPostRepository->syncCategories($post->id, $data['category_ids']);
                }

                return $post->load('categories');
            });

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $post,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create blog post.',
            ];
        }
    }

    /**
     * Update an existing blog post (Admin).
     * (Cập nhật bài viết Blog - Admin)
     */
    public function updateBlogPost(int $id, array $data): array
    {
        $post = $this->blogPostRepository->find($id);

        if (! $post) {
            return [
                'status' => HttpStatusCode::NOT_FOUND->value,
                'message' => 'Blog post not found.',
            ];
        }

        try {
            $updatedPost = DB::transaction(function () use ($id, $data, $post) {
                if (! empty($data['title'])) {
                    $data['slug'] = Str::slug($data['title']);

                    // Ensure slug is unique if title changed
                    if ($data['slug'] !== $post->slug) {
                        $originalSlug = $data['slug'];
                        $count = 1;
                        while ($this->blogPostRepository->firstWhere(['slug' => $data['slug']])) {
                            $data['slug'] = $originalSlug.'-'.$count++;
                        }
                    }
                }

                // If status changed to published, set published_at if null
                if (! empty($data['status']) && $data['status'] === 'published' && empty($post->published_at) && empty($data['published_at'])) {
                    $data['published_at'] = now();
                }

                $this->blogPostRepository->update($id, $data);

                if (isset($data['category_ids'])) {
                    $this->blogPostRepository->syncCategories($id, $data['category_ids']);
                }

                return $post->fresh('categories');
            });

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $updatedPost,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update blog post.',
            ];
        }
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

            // Policy: published → set now if null; draft → set null; archived → keep unchanged
            if ($status === 'published' && empty($post->published_at)) {
                $updateData['published_at'] = now();
            } elseif ($status === 'draft') {
                $updateData['published_at'] = null;
            }

            $this->blogPostRepository->update($id, $updateData);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $post->fresh(),
                'message' => 'Blog post status updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update blog post status.',
            ];
        }
    }

    /**
     * Get paginated blog posts for admin view (including drafts).
     * (Lấy danh sách bài viết Blog có phân trang cho admin - bao gồm cả draft)
     */
    public function getAdminPosts(array $filters): array
    {
        try {
            $posts = $this->blogPostRepository->getAdminPosts($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $posts,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve blog posts.',
            ];
        }
    }

    /**
     * Get a single blog post by ID for admin.
     * (Lấy chi tiết bài viết Blog theo ID cho admin)
     */
    public function getAdminPostById(int $id): array
    {
        try {
            $post = $this->blogPostRepository->findWithCategories($id);

            if (! $post) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog post not found.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $post,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve blog post.',
            ];
        }
    }

    /**
     * Update blog post status with specific status value.
     * (Cập nhật trạng thái bài viết Blog với giá trị cụ thể)
     */
    public function updatePostStatus(int $id, string $status): array
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

            // Policy: published → set now if null; draft → set null; archived → keep unchanged
            if ($status === 'published' && empty($post->published_at)) {
                $updateData['published_at'] = now();
            } elseif ($status === 'draft') {
                $updateData['published_at'] = null;
            }

            $this->blogPostRepository->update($id, $updateData);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $post->fresh(),
                'message' => 'Blog post status updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update blog post status.',
            ];
        }
    }

    /**
     * Create a new blog category.
     * (Tạo danh mục Blog mới)
     */
    public function createCategory(array $data): array
    {
        try {
            // Auto-generate slug if not provided
            if (empty($data['slug']) && ! empty($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure slug is unique (max 60 chars for DB constraint)
            if (! empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueCategorySlug($data['slug']);
            }

            $category = $this->blogCategoryRepository->create($data);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $category,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create blog category.',
            ];
        }
    }

    /**
     * Generate unique slug for blog category.
     * (Tạo slug duy nhất cho danh mục blog)
     */
    private function generateUniqueCategorySlug(string $baseSlug): string
    {
        $slug = substr($baseSlug, 0, 60);
        $count = 1;

        while ($this->blogCategoryRepository->exists(['slug' => $slug])) {
            $suffix = '-'.$count++;
            $slug = substr($baseSlug, 0, 60 - strlen($suffix)).$suffix;
        }

        return $slug;
    }

    /**
     * Update an existing blog category.
     * (Cập nhật danh mục Blog)
     */
    public function updateCategory(int $id, array $data): array
    {
        try {
            $category = $this->blogCategoryRepository->find($id);

            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog category not found.',
                ];
            }

            $this->blogCategoryRepository->update($id, $data);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $category->fresh(),
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update blog category.',
            ];
        }
    }

    /**
     * Delete a blog category.
     * (Xóa danh mục Blog)
     */
    public function deleteCategory(int $id): array
    {
        try {
            $category = $this->blogCategoryRepository->find($id);

            if (! $category) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Blog category not found.',
                ];
            }

            $this->blogCategoryRepository->delete($id);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Blog category deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete blog category.',
            ];
        }
    }
}
