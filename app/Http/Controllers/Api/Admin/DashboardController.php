<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\DashboardValidation;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function locationReports(Request $request): JsonResponse
    {
        $validator = DashboardValidation::validateLocationReports($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->dashboardService->getLocationReports($validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get rating reports.
     * (Lấy báo cáo đánh giá)
     */
    public function ratingReports(Request $request): JsonResponse
    {
        $validator = DashboardValidation::validateRatingReports($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->dashboardService->getRatingReports($validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get user reports.
     * (Lấy báo cáo người dùng)
     */
    public function userReports(Request $request): JsonResponse
    {
        $validator = DashboardValidation::validateUserReports($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->dashboardService->getUserReports($validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get point transaction reports.
     * (Lấy báo cáo giao dịch điểm)
     */
    public function pointReports(Request $request): JsonResponse
    {
        $validator = DashboardValidation::validatePointReports($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->dashboardService->getPointReports($validator->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
