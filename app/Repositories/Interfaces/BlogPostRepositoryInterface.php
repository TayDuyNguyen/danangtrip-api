<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface BlogPostRepositoryInterface
 * (Giao diện Repository cho bài viết Blog)
 */
interface BlogPostRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated blog posts for public view.
     * (Lấy danh sách bài viết Blog có phân trang cho chế độ công khai)
     */
    public function getPublicPosts(array $filters): LengthAwarePaginator;

    /**
     * Get a single public blog post by slug.
     * (Lấy chi tiết một bài viết Blog theo slug cho chế độ công khai)
     *
     * @return mixed
     */
    public function findPublicBySlug(string $slug);

    /**
     * Increment view count for a blog post.
     * (Tăng lượt xem cho bài viết)
     */
    public function incrementViewCount(int $id): int;

    /**
     * Sync categories for a blog post.
     * (Đồng bộ danh mục cho bài viết Blog)
     */
    public function syncCategories(int $postId, array $categoryIds): void;

    /**
     * Get paginated blog posts for admin view (including drafts).
     * (Lấy danh sách bài viết Blog có phân trang cho admin - bao gồm cả draft)
     */
    public function getAdminPosts(array $filters): LengthAwarePaginator;

    /**
     * Find a blog post by ID with categories and author.
     * (Tìm bài viết Blog theo ID với danh mục và tác giả)
     *
     * @return mixed
     */
    public function findWithCategories(int $id);
}
