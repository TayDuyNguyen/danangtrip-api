<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
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
        protected NotificationRepositoryInterface $notificationRepository,
        protected UserRepositoryInterface $userRepository
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
    public function deleteNotification(?int $userId, int $notificationId): array
    {
        try {
            if ($userId === null || $userId === 0) {
                // Admin xóa
                $deleted = $this->notificationRepository->delete($notificationId);
            } else {
                // User xóa thông báo của mình
                $deleted = $this->notificationRepository->deleteForUser($userId, $notificationId);
            }

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
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete notification.',
            ];
        }
    }

    /**
     * Get unread notifications count.
     * (Lấy số lượng thông báo chưa đọc)
     */
    public function getUnreadCount(int $userId): array
    {
        try {
            $count = $this->notificationRepository->getUnreadCount($userId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['unread_count' => $count],
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve unread count.',
            ];
        }
    }

    /**
     * Get admin notifications.
     * (Lấy danh sách thông báo cho admin)
     */
    public function getAdminNotifications(array $filters): array
    {
        try {
            $notifications = $this->notificationRepository->getAdminNotifications($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $notifications,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve admin notifications.',
            ];
        }
    }

    /**
     * Send notification to a specific user.
     * (Gửi thông báo cho một người dùng cụ thể)
     */
    public function sendNotification(array $data): array
    {
        try {
            $notification = $this->notificationRepository->create([
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'title' => $data['title'],
                'content' => $data['content'],
                'data' => $data['data'] ?? null,
                'is_read' => false,
                'created_at' => now(),
            ]);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $notification,
                'message' => 'Notification sent successfully.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to send notification.',
            ];
        }
    }

    /**
     * Send notification to all users.
     * (Gửi thông báo cho tất cả người dùng)
     */
    public function sendNotificationToAll(array $data): array
    {
        try {
            $this->userRepository->chunkAll(500, function ($users) use ($data) {
                $notifications = $users->map(fn ($user) => [
                    'user_id' => $user->id,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'data' => isset($data['data']) ? json_encode($data['data']) : null,
                    'is_read' => false,
                    'created_at' => now(),
                ])->toArray();

                $this->notificationRepository->insert($notifications);
            });

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Initial notifications sent to all users.',
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to send notifications to all users.',
            ];
        }
    }
}
