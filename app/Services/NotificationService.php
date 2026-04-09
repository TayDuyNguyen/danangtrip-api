<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Class NotificationService
 * (Dịch vụ xử lý các hoạt động liên quan đến thông báo)
 */
final class NotificationService
{
    /**
     * NotificationService constructor.
     * (Khởi tạo NotificationService)
     */
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Get user notifications.
     * (Lấy danh sách thông báo của người dùng)
     */
    public function getNotifications(int $userId, array $filters): array
    {
        try {
            $notifications = $this->notificationRepository->getByUserPaginated($userId, $filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $notifications,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve notifications.',
            ];
        }
    }

    /**
     * Mark a notification as read.
     * (Đánh dấu một thông báo là đã đọc)
     */
    public function markAsRead(int $userId, int $notificationId): array
    {
        try {
            $notification = $this->notificationRepository->markAsRead($userId, $notificationId);

            if (! $notification) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Notification not found or you do not have permission to access it.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $notification,
                'message' => 'Notification marked as read.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark notification as read.',
            ];
        }
    }

    /**
     * Mark all notifications as read.
     * (Đánh dấu tất cả thông báo là đã đọc)
     */
    public function markAllAsRead(int $userId): array
    {
        try {
            $updatedCount = $this->notificationRepository->markAllAsRead($userId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['updated_count' => $updatedCount],
                'message' => "{$updatedCount} notifications marked as read.",
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark all notifications as read.',
            ];
        }
    }

    /**
     * Delete a notification.
     * (Xóa một thông báo)
     */
    public function deleteNotification(int $userId, int $notificationId): array
    {
        try {
            $deleted = $this->notificationRepository->deleteForUser($userId, $notificationId);

            if (! $deleted) {
                return [
                    'status' => HttpStatusCode::NOT_FOUND->value,
                    'message' => 'Notification not found or you do not have permission to delete it.',
                ];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Notification deleted successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete notification.',
            ];
        }
    }
}
