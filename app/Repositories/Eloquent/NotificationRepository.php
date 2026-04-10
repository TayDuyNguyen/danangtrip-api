<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class NotificationRepository
 * Eloquent implementation of NotificationRepositoryInterface.
 * (Thực thi Eloquent cho NotificationRepositoryInterface)
 */
final class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     */
    public function getModel(): string
    {
        return Notification::class;
    }

    /**
     * Get paginated notifications for a user.
     * (Lấy danh sách thông báo có phân trang cho người dùng)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if (isset($filters['is_read'])) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;

        return $query->paginate($perPage);
    }

    /**
     * Mark a notification as read.
     * (Đánh dấu một thông báo là đã đọc)
     */
    public function markAsRead(int $userId, int $notificationId): ?Notification
    {
        /** @var Notification|null $notification */
        $notification = $this->firstWhere(['user_id' => $userId, 'id' => $notificationId]);

        if (! $notification || $notification->is_read) {
            return $notification;
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification;
    }

    /**
     * Mark all unread notifications as read for a user.
     * (Đánh dấu tất cả thông báo chưa đọc là đã đọc cho người dùng)
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete a notification for a user.
     * (Xóa một thông báo cho người dùng)
     */
    public function deleteForUser(int $userId, int $notificationId): bool
    {
        return $this->deleteBy(['user_id' => $userId, 'id' => $notificationId]) > 0;
    }

    /**
     * Get unread notifications count for a user.
     * (Lấy số lượng thông báo chưa đọc của người dùng)
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get paginated notifications for admin.
     * (Lấy danh sách thông báo cho admin có phân trang)
     */
    public function getAdminNotifications(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with('user:id,full_name,email')
            ->orderByDesc('created_at');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = $filters['per_page'] ?? Pagination::PER_PAGE->value;

        return $query->paginate($perPage);
    }
}
