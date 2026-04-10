<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

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
        protected RatingRepositoryInterface $ratingRepository,
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
                'total_ratings' => $this->ratingRepository->count(),
                'total_views' => $this->locationRepository->getTotalViewCount(),
            ];

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $stats,
            ];
        } catch (Exception $e) {
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve overview statistics.',
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
            Log::error($e);

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
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve rating reports.',
            ];
        }
    }

    /**
     * Get user reports.
     * (Lấy báo cáo người dùng)
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
}
