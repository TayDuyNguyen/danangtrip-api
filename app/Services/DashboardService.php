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
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
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
     * (Láº¥y thá»‘ng kÃª chi tiáº¿t: ngÆ°á»i dÃ¹ng, tour, Ä‘áº·t tour, doanh thu)
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
     * (Lấy thông tin doanh thu theo khoảng thời gian)
     */
    public function getRevenue(array $filters): array
    {
        try {
            $period = $filters['period'] ?? 'month';
            $from = $filters['from'] ?? null;
            $to = $filters['to'] ?? null;
            $tz = config('app.timezone');

            if ($period === 'day') {
                $from = Carbon::now($tz)->startOfDay()->toDateTimeString();
                $to = Carbon::now($tz)->toDateTimeString();
            } elseif ($period === 'month') {
                $from = Carbon::now($tz)->startOfMonth()->toDateTimeString();
                $to = Carbon::now($tz)->toDateTimeString();
            } elseif ($period === 'week') {
                $from = Carbon::now($tz)->startOfWeek()->toDateTimeString();
                $to = Carbon::now($tz)->toDateTimeString();
            } elseif ($period === 'year') {
                $from = Carbon::now($tz)->startOfYear()->toDateTimeString();
                $to = Carbon::now($tz)->toDateTimeString();
            }

            $rawData = $this->paymentRepository->getRevenueByPeriod($period, $from, $to);
            $dataMap = collect($rawData)->mapWithKeys(function (array $row) use ($period, $tz) {
                $key = $row['period'];
                if ($period === 'day') {
                    $key = (int) $key;
                } elseif ($period === 'week' || $period === 'month') {
                    if ($key instanceof CarbonInterface) {
                        $key = $key->format('Y-m-d');
                    } else {
                        $key = Carbon::parse((string) $key, $tz)->toDateString();
                    }
                }

                return [$key => $row];
            });
            $stats = [];

            if ($period === 'day') {
                $currentHour = Carbon::now($tz)->hour;
                for ($hour = 0; $hour <= $currentHour; $hour++) {
                    $item = $dataMap->get($hour);
                    $stats[] = [
                        'period' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                        'total_revenue' => $item['total_revenue'] ?? '0',
                        'transaction_count' => $item['transaction_count'] ?? 0,
                    ];
                }
            } elseif ($period === 'week') {
                $carbonPeriod = CarbonPeriod::create($from, Carbon::now($tz)->toDateString());
                foreach ($carbonPeriod as $date) {
                    $key = $date->format('Y-m-d');
                    $item = $dataMap->get($key);
                    $stats[] = [
                        'period' => $key,
                        'total_revenue' => $item['total_revenue'] ?? '0',
                        'transaction_count' => $item['transaction_count'] ?? 0,
                    ];
                }
            } elseif ($period === 'month') {
                $carbonPeriod = CarbonPeriod::create(
                    Carbon::parse($from, $tz)->toDateString(),
                    Carbon::now($tz)->toDateString()
                );
                foreach ($carbonPeriod as $date) {
                    $key = $date->format('Y-m-d');
                    $item = $dataMap->get($key);
                    $stats[] = [
                        'period' => $key,
                        'total_revenue' => $item['total_revenue'] ?? '0',
                        'transaction_count' => $item['transaction_count'] ?? 0,
                    ];
                }
            } elseif ($period === 'year') {
                $start = Carbon::parse($from, $tz)->startOfYear()->startOfMonth();
                $end = Carbon::now($tz)->endOfMonth();
                $carbonPeriod = CarbonPeriod::create($start, '1 month', $end);
                foreach ($carbonPeriod as $date) {
                    $key = $date->format('Y-m');
                    $item = $dataMap->get($key);
                    $stats[] = [
                        'period' => $key,
                        'total_revenue' => $item['total_revenue'] ?? '0',
                        'transaction_count' => $item['transaction_count'] ?? 0,
                    ];
                }
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'period' => $period,
                    'from' => $from,
                    'to' => $to,
                    'stats' => $stats,
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
     * Get user growth grouped by month for the last 12 months.
     * (Lấy số lượng người dùng mới theo tháng trong 12 tháng gần nhất)
     */
    public function getUserGrowth(): array
    {
        try {
            $data = $this->userRepository->getNewUsersLast12Months();

            // Backfill 12 months
            $months = collect();
            $current = now()->subMonths(11)->startOfMonth();
            $end = now()->endOfMonth();

            while ($current->lte($end)) {
                $months->push($current->format('Y-m'));
                $current->addMonth();
            }

            $dataMap = collect($data)->keyBy('month');

            $stats = $months->map(fn ($month) => [
                'month' => $month,
                'count' => $dataMap[$month]['count'] ?? 0,
            ])->toArray();

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'stats' => $stats,
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

            $from = now()->subDays($days)->format('Y-m-d');
            $to = now()->format('Y-m-d');

            $dates = collect(CarbonPeriod::create($from, $to))
                ->map(fn (CarbonInterface $d) => $d->format('Y-m-d'));

            $dataMap = collect($data)->keyBy('date');

            $data = $dates->map(function ($date) use ($dataMap) {
                $item = $dataMap->get($date);

                return [
                    'date' => $date,
                    'count' => $item['count'] ?? 0,
                ];
            })->toArray();

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
     * Get booking counts grouped by status.
     * (Lấy số lượng đơn đặt tour theo trạng thái)
     */
    public function getBookingStatusCounts(array $filters): array
    {
        try {
            $data = $this->bookingRepository->getStatusCounts($filters);

            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $result = [];
            foreach ($statuses as $status) {
                $result[$status] = (int) ($data[$status] ?? 0);
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $result,
            ];
        } catch (Exception $e) {
            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to retrieve booking status counts.',
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
     * (Lấy báo cáo người dùng theo tháng)
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
