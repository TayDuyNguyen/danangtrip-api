<?php

namespace App\Repositories\Interfaces;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface ContactRepositoryInterface
 * Define standard operations for Contact repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Liên hệ)
 */
interface ContactRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated contacts with optional status filter.
     * (Lấy danh sách liên hệ có phân trang với bộ lọc trạng thái)
     */
    public function getPaginated(array $filters): LengthAwarePaginator;

    /**
     * Get all contacts for export with optional status filter.
     * (Lấy tất cả liên hệ để export với bộ lọc trạng thái)
     */
    public function getAllForExport(array $filters): Collection;

    /**
     * Mark a contact as read.
     * (Đánh dấu liên hệ là đã đọc)
     */
    public function markAsRead(int $id): bool;

    /**
     * Reply to a contact.
     * (Trả lời liên hệ)
     */
    public function reply(int $id, string $reply, int $repliedBy): bool;
}
