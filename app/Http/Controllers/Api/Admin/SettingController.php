<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

/**
 * Class SettingController
 * (Điều khiển quản lý cấu hình website dành cho Quản trị viên)
 */
final class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService
    ) {}

    /**
     * Get all website settings.
     */
    public function index(): JsonResponse
    {
        $result = $this->settingService->getAdminSettings();

        if ($result['status'] !== 200) {
            return $this->error($result['message'] ?? 'Failed to retrieve website configurations.', $result['status']);
        }

        return $this->success($result['data'], 'Website configurations retrieved successfully.');
    }

    /**
     * Update website configurations.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $result = $this->settingService->updateSettings($request->validated()['settings']);

        if ($result['status'] !== 200) {
            return $this->error($result['message'] ?? 'Failed to update website configuration.', $result['status']);
        }

        return $this->success(null, $result['message']);
    }
}
