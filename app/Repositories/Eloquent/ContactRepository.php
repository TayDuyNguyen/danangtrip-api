<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Contact;
use App\Repositories\Interfaces\ContactRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class ContactRepository
 * Eloquent implementation of ContactRepositoryInterface.
 * (Thực thi Eloquent cho ContactRepositoryInterface)
 */
class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Contact::class;
    }

    /**
     * Get paginated contacts with optional status filter.
     * (Lấy danh sách liên hệ có phân trang với bộ lọc trạng thái)
     */
    public function getPaginated(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;
        $page = $filters['page'] ?? Pagination::PAGE->value;

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all contacts for export with optional status filter.
     * (Lấy tất cả liên hệ để export với bộ lọc trạng thái)
     */
    public function getAllForExport(array $filters): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Mark a contact as read.
     * (Đánh dấu liên hệ là đã đọc)
     */
    public function markAsRead(int $id): bool
    {
        $contact = $this->find($id);

        if (! $contact || $contact->status !== 'new') {
            return false;
        }

        return (bool) $contact->update(['status' => 'read']);
    }

    /**
     * Reply to a contact.
     * (Trả lời liên hệ)
     */
    public function reply(int $id, string $reply, int $repliedBy): bool
    {
        return (bool) $this->update($id, [
            'reply' => $reply,
            'replied_by' => $repliedBy,
            'replied_at' => now(),
            'status' => 'replied',
        ]);
    }
}
