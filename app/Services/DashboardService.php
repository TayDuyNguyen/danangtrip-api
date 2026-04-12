<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\BlogPostRepositoryInterface;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Exception;

/**
 * Class DashboardService
 * Handles business logic for admin dashboard and reports.
 * (Xử lý logic nghiệp vụ cho dashboard và báo cáo của admin)
 */
final class DashboardService
{
    /**
     * DashboardService constructor.
     * (Khởi tạo DashboardService)
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected LocationRepositoryInterface $locationRepository,
        protected TourRepositoryInterface $tourRepository,
        protected RatingRepositoryInterface $ratingRepository,
        protected BlogPostRepositoryInterface $blogPostRepository,
        protected BookingRepositoryInterface $bookingRepository,
        protected PaymentRepositoryInterface $paymentRepository,
    ) {}

    /**
     * Get overview statistics for dashboard.
     * (Lấy thống kê tổng quan cho dashboard)
     */
    public function getOverviewStats(): array
    {
        try {
            $stats = [
                'total_users' => $this->userRepository->count(),
                'total_locations' => $this->locationRepository->count(),
                'total_tours' => $this->tourRepository->count(),
                'total_ratings' => $this->ratingRepository->count(),
                'total_views' => $this->locationRepository->getTotalViewCount(),
                'total_blog_posts' => $this->blogPostRepository->count(),
            ];

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $stats,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve overview statistics.',
            ];
        }
    }

    /**
     * Get detailed stats: users, tours, bookings, revenue.
     * (Lấy thống kê chi tiết: người dùng, tour, đặt tour, doanh thu)
     */
    public function getStats(): array
    {
        try {
            $data = [
                'total_users' => $this->userRepository->count(),
                'total_tours' => $this->tourRepository->count(),
                'total_bookings' => $this->bookingRepository->getTotalCount(),
                'total_revenue' => $this->paymentRepository->getTotalRevenue(),
            ];

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve stats.',
            ];
        }
    }

    /**
     * Get revenue statistics grouped by period.
     * (Lấy thống kê doanh thu theo khoảng thời gian)
     */
    public function getRevenue(array $filters): array
    {
        try {
            $period = $filters['period'] ?? 'month';
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            $data = $this->paymentRepository->getRevenueByPeriod($period, $from, $to);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'period' => $period,
                    'from' => $from,
                    'to' => $to,
                    'stats' => $data,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve revenue statistics.',
            ];
        }
    }

    /**
     * Get top tours by booking count.
     * (Lấy top tour bán chạy)
     */
    public function getTopTours(array $filters): array
    {
        try {
            $limit = $filters['limit'] ?? 10;
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            $data = $this->bookingRepository->getTopTours($limit, $from, $to);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve top tours.',
            ];
        }
    }

    /**
     * Get top locations by favorite and view count.
     * (Lấy top địa điểm được yêu thích)
     */
    public function getTopLocations(array $filters): array
    {
        try {
            $limit = $filters['limit'] ?? 10;

            $data = $this->locationRepository->getTopLocations($limit);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve top locations.',
            ];
        }
    }

    /**
     * Get user growth grouped by month for a given year.
     * (Lấy tăng trưởng người dùng theo tháng trong năm)
     */
    public function getUserGrowth(array $filters): array
    {
        try {
            $year = $filters['year'] ?? (int) date('Y');

            $data = $this->userRepository->getNewUsersByMonth($year);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'year' => $year,
                    'stats' => $data,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve user growth.',
            ];
        }
    }

    /**
     * Get booking trend grouped by date for the last N days.
     * (Lấy xu hướng đặt tour theo ngày trong N ngày gần nhất)
     */
    public function getBookingTrend(array $filters): array
    {
        try {
            $days = $filters['days'] ?? 30;

            $data = $this->bookingRepository->getBookingTrend($days);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'days' => $days,
                    'stats' => $data,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking trend.',
            ];
        }
    }

    /**
     * Get location reports.
     * (Lấy báo cáo địa điểm)
     */
    public function getLocationReports(array $filters): array
    {
        try {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            $data = $this->locationRepository->getStatsByCategoryAndDistrict($from, $to);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve location reports.',
            ];
        }
    }

    /**
     * Get rating reports.
     * (Lấy báo cáo đánh giá)
     */
    public function getRatingReports(array $filters): array
    {
        try {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;
            $status = $filters['status'] ?? null;

            $data = $this->ratingRepository->getStatsByDateAndStatus($from, $to, $status);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve rating reports.',
            ];
        }
    }

    /**
     * Get user reports grouped by month.
     * (Lấy báo cáo người dùng mới theo tháng)
     */
    public function getUserReports(array $filters): array
    {
        try {
            $year = $filters['year'] ?? (int) date('Y');

            $data = $this->userRepository->getNewUsersByMonth($year);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'year' => $year,
                    'stats' => $data,
                ],
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve user reports.',
            ];
        }
    }

    /**
     * Get booking reports grouped by status and date.
     * (Lấy báo cáo đơn hàng theo trạng thái và ngày)
     */
    public function getBookingReports(array $filters): array
    {
        try {
            $data = $this->bookingRepository->getBookingReport($filters);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking reports.',
            ];
        }
    }

    /**
     * Get detailed revenue report grouped by tour.
     * (Lấy báo cáo doanh thu chi tiết theo tour)
     */
    public function getRevenueDetail(array $filters): array
    {
        try {
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;

            $data = $this->paymentRepository->getRevenueDetailByTour($from, $to);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $data,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve revenue detail.',
            ];
        }
    }
}
