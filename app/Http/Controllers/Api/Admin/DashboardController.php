<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\LocationReportsDashboardRequest;
use App\Http\Requests\Dashboard\PointReportsDashboardRequest;
use App\Http\Requests\Dashboard\RatingReportsDashboardRequest;
use App\Http\Requests\Dashboard\UserReportsDashboardRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * Class DashboardController
 * Handles administrative API requests for dashboard and reports.
 * (Xử lý các yêu cầu API quản trị cho dashboard và báo cáo)
 */
final class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Get overview statistics.
     * (Lấy thống kê tổng quan)
     */
    public function overview(): JsonResponse
    {
        $result = $this->dashboardService->getOverviewStats();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get location reports.
     * (Lấy báo cáo địa điểm)
     */
    public function locationReports(LocationReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getLocationReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get rating reports.
     * (Lấy báo cáo đánh giá)
     */
    public function ratingReports(RatingReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getRatingReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get user reports.
     * (Lấy báo cáo người dùng)
     */
    public function userReports(UserReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getUserReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get point transaction reports.
     * (Lấy báo cáo giao dịch điểm)
     */
    public function pointReports(PointReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getPointReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
