<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SystemOverviewExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new class($this->data['overview']) implements FromArray, WithTitle {
                protected array $overview;

                public function __construct($overview)
                {
                    $this->overview = is_array($overview) ? $overview : (is_object($overview) && method_exists($overview, 'toArray') ? $overview->toArray() : (array)$overview);
                }

                public function array(): array
                {
                    return [
                        ['Chỉ số thống kê', 'Số liệu tích lũy', '30 ngày gần đây', '30 ngày trước đó', 'Tỷ lệ tăng trưởng (%)'],
                        ['Người dùng mới', $this->overview['total_users'] ?? 0, $this->overview['current_30day_users'] ?? 0, $this->overview['prev_30day_users'] ?? 0, ($this->overview['user_trend'] ?? 0) . '%'],
                        ['Đơn đặt tour', $this->overview['total_bookings'] ?? 0, $this->overview['current_30day_bookings'] ?? 0, $this->overview['prev_30day_bookings'] ?? 0, ($this->overview['booking_trend'] ?? 0) . '%'],
                        ['Doanh thu (VNĐ)', number_format($this->overview['total_revenue'] ?? 0, 0, ',', '.'), number_format($this->overview['current_30day_revenue'] ?? 0, 0, ',', '.'), number_format($this->overview['prev_30day_revenue'] ?? 0, 0, ',', '.'), ($this->overview['revenue_trend'] ?? 0) . '%'],
                        ['Tổng số địa điểm', $this->overview['total_locations'] ?? 0, '-', '-', '-'],
                        ['Tổng số tour', $this->overview['total_tours'] ?? 0, '-', '-', '-'],
                        ['Lượt đánh giá', $this->overview['total_ratings'] ?? 0, '-', '-', '-'],
                        ['Lượt xem địa điểm', $this->overview['total_views'] ?? 0, '-', '-', '-'],
                        ['Bài viết blog', $this->overview['total_blog_posts'] ?? 0, '-', '-', '-'],
                    ];
                }

                public function title(): string
                {
                    return 'Tổng quan';
                }
            },
            new class($this->data['revenue']) implements FromArray, WithTitle, WithHeadings {
                protected array $revenue;

                public function __construct($revenue)
                {
                    $this->revenue = is_array($revenue) ? $revenue : (is_object($revenue) && method_exists($revenue, 'toArray') ? $revenue->toArray() : (array)$revenue);
                }

                public function headings(): array
                {
                    return ['Thời gian', 'Doanh thu (VNĐ)', 'Số giao dịch'];
                }

                public function array(): array
                {
                    return array_map(fn($item) => [
                        $item['period'] ?? 'N/A',
                        number_format((float)($item['total_revenue'] ?? 0), 0, ',', '.'),
                        $item['transaction_count'] ?? 0
                    ], $this->revenue);
                }

                public function title(): string
                {
                    return 'Doanh thu';
                }
            },
            new class($this->data['booking_trend']) implements FromArray, WithTitle, WithHeadings {
                protected array $bookingTrend;

                public function __construct($bookingTrend)
                {
                    $this->bookingTrend = is_array($bookingTrend) ? $bookingTrend : (is_object($bookingTrend) && method_exists($bookingTrend, 'toArray') ? $bookingTrend->toArray() : (array)$bookingTrend);
                }

                public function headings(): array
                {
                    return ['Ngày', 'Số lượt đặt tour'];
                }

                public function array(): array
                {
                    return array_map(fn($item) => [
                        $item['date'] ?? 'N/A',
                        $item['count'] ?? 0
                    ], $this->bookingTrend);
                }

                public function title(): string
                {
                    return 'Xu hướng đặt tour';
                }
            },
            new class($this->data['user_growth']) implements FromArray, WithTitle, WithHeadings {
                protected array $userGrowth;

                public function __construct($userGrowth)
                {
                    $this->userGrowth = is_array($userGrowth) ? $userGrowth : (is_object($userGrowth) && method_exists($userGrowth, 'toArray') ? $userGrowth->toArray() : (array)$userGrowth);
                }

                public function headings(): array
                {
                    return ['Tháng', 'Số người dùng mới'];
                }

                public function array(): array
                {
                    return array_map(fn($item) => [
                        $item['month'] ?? 'N/A',
                        $item['count'] ?? 0
                    ], $this->userGrowth);
                }

                public function title(): string
                {
                    return 'Tăng trưởng người dùng';
                }
            },
            new class($this->data['top_tours']) implements FromArray, WithTitle, WithHeadings {
                protected array $topTours;

                public function __construct($topTours)
                {
                    $this->topTours = is_array($topTours) ? $topTours : (is_object($topTours) && method_exists($topTours, 'toArray') ? $topTours->toArray() : (array)$topTours);
                }

                public function headings(): array
                {
                    return ['Mã Tour', 'Tên Tour', 'Số lượt đặt', 'Doanh thu (VNĐ)'];
                }

                public function array(): array
                {
                    return array_map(fn($item) => [
                        $item['id'] ?? 'N/A',
                        $item['name'] ?? 'N/A',
                        $item['booking_count'] ?? 0,
                        number_format((float)($item['total_revenue'] ?? 0), 0, ',', '.')
                    ], $this->topTours);
                }

                public function title(): string
                {
                    return 'Top Tour bán chạy';
                }
            },
            new class($this->data['top_locations']) implements FromArray, WithTitle, WithHeadings {
                protected $topLocations;

                public function __construct($topLocations)
                {
                    $this->topLocations = $topLocations;
                }

                public function headings(): array
                {
                    return ['Mã Địa điểm', 'Tên Địa điểm', 'Quận/Huyện', 'Lượt yêu thích', 'Lượt xem', 'Đánh giá trung bình', 'Số nhận xét'];
                }

                public function array(): array
                {
                    return collect($this->topLocations)->map(fn($item) => [
                        $item->id ?? $item['id'] ?? 'N/A',
                        $item->name ?? $item['name'] ?? 'N/A',
                        $item->district ?? $item['district'] ?? 'N/A',
                        $item->favorite_count ?? $item['favorite_count'] ?? 0,
                        $item->view_count ?? $item['view_count'] ?? 0,
                        $item->avg_rating ?? $item['avg_rating'] ?? '0.00',
                        $item->review_count ?? $item['review_count'] ?? 0
                    ])->toArray();
                }

                public function title(): string
                {
                    return 'Top Địa điểm nổi bật';
                }
            }
        ];
    }
}
