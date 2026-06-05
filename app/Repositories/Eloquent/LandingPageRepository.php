<?php

namespace App\Repositories\Eloquent;

use App\Models\LandingPage;
use App\Repositories\Interfaces\LandingPageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class LandingPageRepository
 * (Thực thi Repository cho Landing Pages)
 */
final class LandingPageRepository extends BaseRepository implements LandingPageRepositoryInterface
{
    /**
     * Get the model class.
     */
    public function getModel(): string
    {
        return LandingPage::class;
    }

    /**
     * Get paginated admin list with filters.
     */
    public function adminList(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Search by slug or title
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                // ILIKE is used for case-insensitive search in PostgreSQL, LIKE for MySQL
                // Base on active schema, using ILIKE or fallback
                $q->where('slug', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        // Filter by page_type
        if (! empty($filters['page_type'])) {
            $query->where('page_type', $filters['page_type']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Find landing page by unique slug.
     */
    public function findBySlug(string $slug): ?object
    {
        return $this->model->newQuery()
            ->where('slug', strtolower(trim($slug)))
            ->first();
    }

    /**
     * Toggle landing page status.
     */
    public function toggleStatus(int $id, string $status): bool
    {
        return (bool) $this->model->newQuery()
            ->where('id', $id)
            ->update(['status' => $status]);
    }
}
