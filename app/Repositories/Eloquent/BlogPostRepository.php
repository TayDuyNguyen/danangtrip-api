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
            ->where('published_at', '<=', now());

        $sort = $filters['sort'] ?? 'latest';
        if ($sort === 'popular') {
            $query->orderByDesc('view_count')->orderByDesc('published_at');
        } else {
            $query->orderByDesc('published_at');
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('blog_categories.id', $filters['category_id']);
            });
        }

        // Search by title or excerpt if provided
        $search = trim((string) ($filters['search'] ?? $filters['q'] ?? ''));
        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';

            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw("LOWER(COALESCE(excerpt, '')) LIKE ?", [$needle]);
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

    /**
     * Get paginated blog posts for admin view (including drafts).
     * (Lấy danh sách bài viết Blog có phân trang cho admin - bao gồm cả draft)
     */
    public function getAdminPosts(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['author:id,full_name,avatar', 'categories']);

        // Filter by status if provided
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by category if provided
        if (! empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('blog_categories.id', $filters['category_id']);
            });
        }

        // Search by title or excerpt if provided
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';

            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw("LOWER(COALESCE(excerpt, '')) LIKE ?", [$needle]);
            });
        }

        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';

        $query->orderBy($sort, $order);

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;

        return $query->paginate($perPage);
    }

    /**
     * Find a blog post by ID with categories and author.
     * (Tìm bài viết Blog theo ID với danh mục và tác giả)
     *
     * @return mixed
     */
    public function findWithCategories(int $id)
    {
        return $this->model->newQuery()
            ->with(['author:id,full_name,avatar', 'categories'])
            ->find($id);
    }

    /**
     * Get counts of blog posts grouped by status.
     * (Lấy số lượng bài viết blog gom nhóm theo trạng thái)
     */
    public function getStatusCounts(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'published' => $this->model->newQuery()->where('status', 'published')->count(),
            'draft' => $this->model->newQuery()->where('status', 'draft')->count(),
            'archived' => $this->model->newQuery()->where('status', 'archived')->count(),
        ];
    }
}
