<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

/**
 * Class SettingController
 * (Điều khiển phân phối cấu hình website công khai cho Frontend)
 */
final class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Get public configurations for frontend web apps.
     * (Lấy cấu hình công khai)
     */
    public function publicConfig(): JsonResponse
    {
        $result = $this->settingService->getPublicSettings();

        if ($result['status'] !== 200) {
            return $this->error($result['message'] ?? 'Failed to retrieve public configuration.', $result['status']);
        }

        return $this->success($result['data'], 'Public configurations retrieved successfully.');
    }
}
