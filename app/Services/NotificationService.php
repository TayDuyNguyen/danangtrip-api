<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Jobs\SendAdminNotificationEmail;
use App\Models\User;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class NotificationService
 * (Dịch vụ xử lý các hoạt động liên quan đến thông báo)
 */
final class NotificationService
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function getNotifications(int $userId, array $filters): array
    {
        try {
            $notifications = $this->notificationRepository->getByUserPaginated($userId, $filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $notifications,
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_LIST_FAILED', $e, ['user_id' => $userId]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve notifications.',
            ];
        }
    }

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
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_MARK_AS_READ_FAILED', $e, [
                'user_id' => $userId,
                'notification_id' => $notificationId,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark notification as read.',
            ];
        }
    }

    public function markAllAsRead(int $userId): array
    {
        try {
            $updatedCount = $this->notificationRepository->markAllAsRead($userId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['updated_count' => $updatedCount],
                'message' => "{$updatedCount} notifications marked as read.",
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_MARK_ALL_AS_READ_FAILED', $e, ['user_id' => $userId]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark all notifications as read.',
            ];
        }
    }

    public function deleteNotification(?int $userId, int $notificationId): array
    {
        try {
            if ($userId === null || $userId === 0) {
                $deleted = $this->notificationRepository->delete($notificationId);
            } else {
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
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_DELETE_FAILED', $e, [
                'user_id' => $userId,
                'notification_id' => $notificationId,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete notification.',
            ];
        }
    }

    public function getUnreadCount(int $userId): array
    {
        try {
            $count = $this->notificationRepository->getUnreadCount($userId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => ['unread_count' => $count],
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_UNREAD_COUNT_FAILED', $e, ['user_id' => $userId]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve unread count.',
            ];
        }
    }

    public function getAdminNotifications(array $filters): array
    {
        try {
            $paginator = $this->notificationRepository->getAdminNotifications($filters);
            $data = $paginator instanceof Arrayable
                ? $paginator->toArray()
                : [];
            $data['stats'] = [
                'total' => $this->notificationRepository->count(),
                'read' => $this->notificationRepository->countByReadStatus(true),
                'unread' => $this->notificationRepository->countByReadStatus(false),
            ];

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_ADMIN_LIST_FAILED', $e, ['filters' => $filters]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve admin notifications.',
            ];
        }
    }

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

            $user = $this->userRepository->find($data['user_id']);
            if ($user instanceof User) {
                $this->queueMailToUser($user, $data);
            }

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $notification,
                'message' => 'Notification sent successfully.',
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_SEND_FAILED', $e, [
                'user_id' => $data['user_id'] ?? null,
                'type' => $data['type'] ?? null,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to send notification.',
            ];
        }
    }

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

                $users->each(fn (User $user) => $this->queueMailToUser($user, $data));
            });

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Initial notifications sent to all users.',
            ];
        } catch (Throwable $e) {
            $this->logFailure('NOTIFICATION_SEND_ALL_FAILED', $e, [
                'type' => $data['type'] ?? null,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to send notifications to all users.',
            ];
        }
    }

    private function queueMailToUser(User $user, array $data): void
    {
        if (empty($user->email)) {
            return;
        }

        try {
            SendAdminNotificationEmail::dispatch(
                email: $user->email,
                title: $data['title'],
                content: $data['content'],
                type: $data['type'],
                data: $data['data'] ?? null,
                recipientName: $user->full_name,
                userId: $user->id,
            );

            Log::info('Notification email queued.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $data['type'],
                'title' => $data['title'],
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to queue notification email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    private function logFailure(string $event, Throwable $e, array $context = []): void
    {
        Log::error($event, array_merge($context, [
            'message' => $e->getMessage(),
            'exception' => $e::class,
        ]));
    }
}
