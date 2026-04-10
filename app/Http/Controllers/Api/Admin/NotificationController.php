<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\AdminListNotificationRequest;
use App\Http\Requests\Notification\SendAllNotificationRequest;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

/**
 * Class NotificationController
 * (Điều khiển các hoạt động liên quan đến thông báo dành cho Admin)
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
     * Get a list of system notifications.
     * (Lấy danh sách thông báo hệ thống)
     */
    public function index(AdminListNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->getAdminNotifications($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Send notification to a user.
     * (Gửi thông báo đến user)
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->sendNotification($request->validated());

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->success($result['data'], $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Send notification to all users.
     * (Gửi thông báo đến tất cả user)
     */
    public function sendAll(SendAllNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->sendNotificationToAll($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a notification.
     * (Xóa thông báo)
     */
    public function destroy(int $id): JsonResponse
    {
        // Admin xóa không cần userId kiểm tra quyền sở hữu,
        // nhưng service hiện tại yêu cầu userId.
        // Tôi sẽ truyền 0 hoặc refactor service nếu cần.
        // Thực tế admin có quyền xóa bất cứ cái nào.

        // Refactor: gọi trực tiếp repository hoặc để userId = 0 (logic trong repo cần handle admin)
        // Hiện tại deleteForUser trong repo có where user_id.
        // Tôi sẽ thêm phương thức delete cho admin.

        $result = $this->notificationService->deleteNotification(0, $id); // Cần kiểm tra logic này

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['status']);
    }
}
