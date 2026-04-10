<?php

namespace App\Repositories\Interfaces;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface NotificationRepositoryInterface
 * Define standard operations for Notification repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Thông báo)
 */
interface NotificationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated notifications for a user.
     * (Lấy danh sách thông báo có phân trang cho người dùng)
     */
    public function getByUserPaginated(int $userId, array $filters): LengthAwarePaginator;

    /**
     * Mark a notification as read.
     * (Đánh dấu một thông báo là đã đọc)
     */
    public function markAsRead(int $userId, int $notificationId): ?Notification;

    /**
     * Mark all unread notifications as read for a user.
     * (Đánh dấu tất cả thông báo chưa đọc là đã đọc cho người dùng)
     *
     * @return int The number of updated notifications.
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Delete a notification for a user.
     * (Xóa một thông báo cho người dùng)
     */
    public function deleteForUser(int $userId, int $notificationId): bool;

    /**
     * Get unread notifications count for a user.
     * (Lấy số lượng thông báo chưa đọc của người dùng)
     */
    public function getUnreadCount(int $userId): int;

    /**
     * Get paginated notifications for admin.
     * (Lấy danh sách thông báo cho admin có phân trang)
     */
    public function getAdminNotifications(array $filters): LengthAwarePaginator;
}
