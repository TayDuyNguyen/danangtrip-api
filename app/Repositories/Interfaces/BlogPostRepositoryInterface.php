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
}
