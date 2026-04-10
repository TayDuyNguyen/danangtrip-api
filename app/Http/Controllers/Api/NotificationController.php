<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\DeleteNotificationRequest;
use App\Http\Requests\Notification\ListNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class NotificationController
 * (Điều khiển các hoạt động liên quan đến thông báo)
 */
final class NotificationController extends Controller
{
    /**
     * NotificationController constructor.
     * (Khởi tạo NotificationController)
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get a list of notifications.
     * (Lấy danh sách thông báo)
     */
    public function index(ListNotificationRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->notificationService->getNotifications($userId, $request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Mark a notification as read.
     * (Đánh dấu một thông báo là đã đọc)
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->notificationService->markAsRead($userId, $id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Mark all notifications as read.
     * (Đánh dấu tất cả thông báo là đã đọc)
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->notificationService->markAllAsRead($userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a notification.
     * (Xóa một thông báo)
     */
    public function destroy(DeleteNotificationRequest $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->notificationService->deleteNotification($userId, $id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get unread notifications count.
     * (Lấy số lượng thông báo chưa đọc)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->notificationService->getUnreadCount($userId);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
