<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Cache;

/**
 * Class SettingService
 * (Dịch vụ xử lý cấu hình website với cache và tối ưu hóa hiệu suất)
 */
final class SettingService
{
    /**
     * Cache key for public configurations.
     */
    private const CACHE_KEY = 'public_config';

    public function __construct(
        protected SettingRepositoryInterface $settingRepository
    ) {}

    /**
     * Retrieve public configuration (nested format with dynamic caching).
     * (Lấy cấu hình công khai dưới dạng lồng nhau với cơ chế lưu Cache)
     */
    public function getPublicSettings(): array
    {
        try {
            $data = Cache::rememberForever(self::CACHE_KEY, function () {
                $settings = $this->settingRepository->getPublicSettings();

                return $this->formatSettingsToNested($settings);
            });

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve public configuration.',
            ];
        }
    }

    /**
     * Retrieve all configurations for admin dashboard management.
     */
    public function getAdminSettings(): array
    {
        try {
            $settings = $this->settingRepository->getAdminSettings();
            $data = $this->formatSettingsToNested($settings);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve admin settings.',
            ];
        }
    }

    /**
     * Update configurations, atomic transactional-safe and clears public cache.
     */
    public function updateSettings(array $settings): array
    {
        try {
            $this->settingRepository->saveSettings($settings);

            // Invalidate the public configurations cache
            Cache::forget(self::CACHE_KEY);
            Cache::forget('chatbot_db_settings');

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Website configuration updated successfully.',
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update website configuration.',
            ];
        }
    }

    /**
     * Helper to transform flat collection to sectioned nested array.
     */
    private function formatSettingsToNested($settings): array
    {
        $nested = [];

        foreach ($settings as $setting) {
            $parts = explode('.', $setting->key, 2);
            if (count($parts) === 2) {
                $section = $parts[0];
                $key = $parts[1];

                $nested[$section][$key] = $setting->cast_value;
            }
        }

        if (isset($nested['payment']['payos']) && ! isset($nested['payment']['sepay'])) {
            $nested['payment']['sepay'] = $nested['payment']['payos'];
        }
        unset($nested['payment']['payos']);

        return $nested;
    }
}
