<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;

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
}
