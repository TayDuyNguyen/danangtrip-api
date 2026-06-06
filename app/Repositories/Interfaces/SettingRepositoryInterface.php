<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface SettingRepositoryInterface
 * (Giao diện Repository cho Cấu hình Website)
 */
interface SettingRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all public website settings.
     * (Lấy tất cả các cấu hình công khai của website)
     */
    public function getPublicSettings(): Collection;

    /**
     * Get all settings for admin screen.
     * (Lấy tất cả các cấu hình cho màn hình quản lý admin)
     */
    public function getAdminSettings(): Collection;

    /**
     * Save multiple configurations at once.
     * (Lưu nhiều cấu hình cùng lúc)
     */
    public function saveSettings(array $settings): bool;
}
