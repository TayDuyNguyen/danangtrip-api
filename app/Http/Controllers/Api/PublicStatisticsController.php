<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * Class PublicStatisticsController
 * Handles public-facing statistical data.
 * (Xử lý dữ liệu thống kê công khai)
 */
final class PublicStatisticsController extends Controller
{
    /**
     * PublicStatisticsController constructor.
     * (Khởi tạo PublicStatisticsController)
     */
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Get public overview statistics.
     * (Lấy thống kê tổng quan công khai)
     */
    public function overview(): JsonResponse
    {
        $result = $this->dashboardService->getOverviewStats();

        if ($result['status'] === HttpStatusCode::SUCCESS->value) {
            return $this->success($result['data']);
        }

        return $this->error($result['message'], $result['status']);
    }
}
