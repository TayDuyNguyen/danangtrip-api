<?php

namespace App\Repositories\Eloquent;

use App\Models\Promotion;
use App\Repositories\Interfaces\PromotionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class PromotionRepository
 * (Thực thi Repository cho Khuyến mãi)
 */
final class PromotionRepository extends BaseRepository implements PromotionRepositoryInterface
{
    /**
     * Get the model class.
     */
    public function getModel(): string
    {
        return Promotion::class;
    }

    /**
     * Get paginated admin list with filters.
     */
    public function adminList(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Search by code or name
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ILIKE', "%{$search}%")
                    ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by validity
        if (! empty($filters['valid_now'])) {
            $now = now();
            $query->where('status', 'active')
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Find promotion by unique code (case-insensitive).
     */
    public function findByCode(string $code): ?object
    {
        return $this->model->newQuery()
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();
    }

    /**
     * Toggle promotion status.
     */
    public function toggleStatus(int $id, string $status): bool
    {
        return (bool) $this->model->newQuery()
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    /**
     * Get currently valid active promotions for public display.
     */
    public function getActivePromotions(): Collection
    {
        $now = now();

        return $this->model->newQuery()
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
