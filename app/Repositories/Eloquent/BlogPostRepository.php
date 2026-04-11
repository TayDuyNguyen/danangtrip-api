<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\BlogPost;
use App\Repositories\Interfaces\BlogPostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class BlogPostRepository
 * (Lớp triển khai Repository cho bài viết Blog bằng Eloquent)
 */
final class BlogPostRepository extends BaseRepository implements BlogPostRepositoryInterface
{
    /**
     * Specify model class name.
     * (Chỉ định tên lớp Model)
     */
    public function getModel(): string
    {
        return BlogPost::class;
    }

    /**
     * Get paginated blog posts for public view.
     * (Lấy danh sách bài viết Blog có phân trang cho chế độ công khai)
     */
    public function getPublicPosts(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['author:id,full_name,avatar', 'categories'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');

        if (! empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('blog_categories.id', $filters['category_id']);
            });
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;

        return $query->paginate($perPage);
    }

    /**
     * Get a single public blog post by slug.
     * (Lấy chi tiết một bài viết Blog theo slug cho chế độ công khai)
     *
     * @return mixed
     */
    public function findPublicBySlug(string $slug)
    {
        return $this->model->newQuery()
            ->with(['author:id,full_name,avatar', 'categories'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->first();
    }

    /**
     * Increment view count for a blog post.
     * (Tăng lượt xem cho bài viết)
     */
    public function incrementViewCount(int $id): int
    {
        return (int) $this->increment($id, 'view_count');
    }

    /**
     * Sync categories for a blog post.
     * (Đồng bộ danh mục cho bài viết Blog)
     */
    public function syncCategories(int $postId, array $categoryIds): void
    {
        $this->sync($postId, 'categories', $categoryIds);
    }
}
