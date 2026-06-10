<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\HttpStatusCode;
use App\Exports\SystemOverviewExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BookingReportsDashboardRequest;
use App\Http\Requests\Dashboard\BookingTrendDashboardRequest;
use App\Http\Requests\Dashboard\LocationReportsDashboardRequest;
use App\Http\Requests\Dashboard\RatingReportsDashboardRequest;
use App\Http\Requests\Dashboard\RevenueDashboardRequest;
use App\Http\Requests\Dashboard\RevenueDetailDashboardRequest;
use App\Http\Requests\Dashboard\SearchTrendsDashboardRequest;
use App\Http\Requests\Dashboard\TopLocationsDashboardRequest;
use App\Http\Requests\Dashboard\TopToursDashboardRequest;
use App\Http\Requests\Dashboard\UserReportsDashboardRequest;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * Get unread notification counts per category for the admin bell icon.
     * Polled every 30 seconds from the frontend.
     * (Lấy số lượng thông báo chưa đọc theo danh mục cho chuông thông báo admin)
     */
    public function notificationCounts(): JsonResponse
    {
        $result = $this->dashboardService->getNotificationCounts();

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
     * Get search trend widget data.
     * (Lấy dữ liệu widget xu hướng tìm kiếm)
     */
    public function searchTrends(SearchTrendsDashboardRequest $request): JsonResponse
    {
        $result = $this->dashboardService->getSearchTrends($request->validated());

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

    /**
     * Export system overview report to Excel.
     * (Xuất báo cáo tổng quan hệ thống ra Excel)
     */
    public function export(): BinaryFileResponse|JsonResponse
    {
        try {
            // Retrieve all key statistics
            $overviewResult = $this->dashboardService->getOverviewStats();
            $statsResult = $this->dashboardService->getStats();

            $overview = [];
            if ($overviewResult['status'] === HttpStatusCode::SUCCESS->value) {
                $overview = $overviewResult['data'];
            }
            if ($statsResult['status'] === HttpStatusCode::SUCCESS->value) {
                $overview = array_merge($overview, $statsResult['data']);
            }

            // Retrieve other statistics
            $revenueResult = $this->dashboardService->getRevenue(['period' => 'month']);
            $revenue = $revenueResult['status'] === HttpStatusCode::SUCCESS->value ? $revenueResult['data']['stats'] : [];

            $bookingTrendResult = $this->dashboardService->getBookingTrend(['days' => 30]);
            $bookingTrend = $bookingTrendResult['status'] === HttpStatusCode::SUCCESS->value ? $bookingTrendResult['data']['stats'] : [];

            $userGrowthResult = $this->dashboardService->getUserGrowth();
            $userGrowth = $userGrowthResult['status'] === HttpStatusCode::SUCCESS->value ? $userGrowthResult['data']['stats'] : [];

            $topToursResult = $this->dashboardService->getTopTours(['limit' => 10]);
            $topTours = $topToursResult['status'] === HttpStatusCode::SUCCESS->value ? $topToursResult['data'] : [];

            $topLocationsResult = $this->dashboardService->getTopLocations(['limit' => 10]);
            $topLocations = $topLocationsResult['status'] === HttpStatusCode::SUCCESS->value ? $topLocationsResult['data'] : [];

            $data = [
                'overview' => $overview,
                'revenue' => $revenue,
                'booking_trend' => $bookingTrend,
                'user_growth' => $userGrowth,
                'top_tours' => $topTours,
                'top_locations' => $topLocations,
            ];

            $stamp = now()->format('Y-m-d_His');
            $asciiName = "bao-cao-he-thong-{$stamp}.xlsx";
            $utf8Name = "Báo cáo hệ thống {$stamp}.xlsx";

            $response = Excel::download(new SystemOverviewExport($data), $asciiName);

            $safeAscii = str_replace(['"', '\\'], '-', $asciiName);
            $response->headers->set(
                'Content-Disposition',
                'attachment; filename="'.$safeAscii.'"; filename*=UTF-8\'\''.rawurlencode($utf8Name),
                true
            );

            return $response;
        } catch (\Exception $e) {
            return $this->error('Failed to export system report: '.$e->getMessage(), HttpStatusCode::INTERNAL_SERVER_ERROR->value);
        }
    }
}
