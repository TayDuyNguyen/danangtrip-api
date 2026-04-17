<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BookingReportsDashboardRequest;
use App\Http\Requests\Dashboard\BookingTrendDashboardRequest;
use App\Http\Requests\Dashboard\LocationReportsDashboardRequest;
use App\Http\Requests\Dashboard\RatingReportsDashboardRequest;
use App\Http\Requests\Dashboard\RevenueDashboardRequest;
use App\Http\Requests\Dashboard\RevenueDetailDashboardRequest;
use App\Http\Requests\Dashboard\TopLocationsDashboardRequest;
use App\Http\Requests\Dashboard\TopToursDashboardRequest;
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
     * Get detailed stats: users, tours, bookings, revenue.
     * (Lấy thống kê chi tiết: người dùng, tour, đặt tour, doanh thu)
     */
    public function stats(): JsonResponse
    {
        $result = $this->dashboardService->getStats();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get revenue statistics grouped by period.
     * (Lấy thống kê doanh thu theo khoảng thời gian)
     */
    public function revenue(RevenueDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getRevenue($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get top tours by booking count.
     * (Lấy top tour bán chạy)
     */
    public function topTours(TopToursDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getTopTours($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get top locations by favorite and view count.
     * (Lấy top địa điểm được yêu thích)
     */
    public function topLocations(TopLocationsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getTopLocations($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get user growth grouped by month for the last 12 months.
     * (Lấy tăng trưởng người dùng theo tháng trong 12 tháng gần nhất)
     */
    public function userGrowth(): JsonResponse
    {
        $result = $this->dashboardService->getUserGrowth();

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get booking trend for the last N days.
     * (Lấy xu hướng đặt tour trong N ngày gần nhất)
     */
    public function bookingTrend(BookingTrendDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getBookingTrend($request->validated());

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
     * Get user reports grouped by month.
     * (Lấy báo cáo người dùng mới theo tháng)
     */
    public function userReports(UserReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getUserReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get booking reports grouped by status and date.
     * (Lấy báo cáo đơn hàng theo trạng thái và ngày)
     */
    public function bookingReports(BookingReportsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getBookingReports($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Get detailed revenue report grouped by tour.
     * (Lấy báo cáo doanh thu chi tiết theo tour)
     */
    public function revenueDetail(RevenueDetailDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getRevenueDetail($request->validated());

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }
}
