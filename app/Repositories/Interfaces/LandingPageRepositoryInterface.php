<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface LandingPageRepositoryInterface
 * (Giao diện Repository cho Landing Pages)
 */
interface LandingPageRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated list of landing pages with filters.
     * (Lấy danh sách landing pages có phân trang và lọc)
     */
    public function adminList(array $filters): LengthAwarePaginator;

    /**
     * Find landing page by unique slug.
     * (Tìm landing page theo slug duy nhất)
     */
    public function findBySlug(string $slug): ?object;

    /**
     * Toggle status of a landing page.
     * (Đổi trạng thái hiển thị của landing page)
     */
    public function toggleStatus(int $id, string $status): bool;
}
