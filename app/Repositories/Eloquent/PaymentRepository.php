<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Enums\PaymentStatus;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class PaymentRepository
 * (Triển khai Repository cho Thanh toán)
 */
final class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    /**
     * Get the model class.
     * (Lấy lớp Model)
     */
    public function getModel(): string
    {
        return Payment::class;
    }

    /**
     * Get all payments with filters.
     * (Lấy danh sách thanh toán với bộ lọc)
     */
    public function getPayments(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('booking.user');

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereRaw('CAST(created_at AS DATE) >= ?', [$filters['date_from']]);
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw('CAST(created_at AS DATE) <= ?', [$filters['date_to']]);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('transaction_code', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('booking', function ($qb) use ($filters) {
                        $qb->where('booking_code', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? Pagination::PER_PAGE->value);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find payment by transaction code.
     * (Tìm kiếm thanh toán theo mã giao dịch)
     */
    public function findByTransactionCode(string $transactionCode): ?Payment
    {
        return $this->model->newQuery()
            ->where('transaction_code', $transactionCode)
            ->first();
    }

    /**
     * Find payment by transaction code with row lock FOR UPDATE.
     * (Tìm thanh toán theo mã giao dịch và khóa hàng FOR UPDATE)
     */
    public function findByTransactionCodeForUpdate(string $transactionCode): ?Payment
    {
        return $this->model->newQuery()
            ->where('transaction_code', $transactionCode)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Get payments for export.
     * (Lấy danh sách thanh toán để xuất file)
     */
    public function getExportPayments(array $filters): Collection
    {
        $query = $this->model->newQuery()->with('booking.user');

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (! empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereRaw('CAST(created_at AS DATE) >= ?', [$filters['date_from']]);
        }

        if (! empty($filters['date_to'])) {
            $query->whereRaw('CAST(created_at AS DATE) <= ?', [$filters['date_to']]);
        }

        return $query->latest()->get();
    }

    /**
     * Get revenue grouped by period (day/week/month/year).
     * (Lấy doanh thu theo khoảng thời gian)
     */
    public function getRevenueByPeriod(string $period, ?string $from, ?string $to): array
    {
        $tz = config('app.timezone');
        $tzSql = str_replace("'", "''", $tz);

        $paidAtLocal = "((paid_at AT TIME ZONE 'UTC') AT TIME ZONE '{$tzSql}')";

        $groupExpr = match ($period) {
            'day' => "EXTRACT(HOUR FROM {$paidAtLocal})::INTEGER",
            'week', 'month' => "CAST({$paidAtLocal} AS DATE)",
            'year' => "TO_CHAR({$paidAtLocal}, 'YYYY-MM')",
            default => "CAST({$paidAtLocal} AS DATE)",
        };

        [$fromBound, $toBound] = $this->paidAtBounds($from, $to);

        $query = $this->model->newQuery()
            ->selectRaw("{$groupExpr} as period, SUM(amount) as total_revenue, COUNT(*) as transaction_count")
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at');

        if ($fromBound !== null) {
            $query->where('paid_at', '>=', $fromBound);
        }

        if ($toBound !== null) {
            $query->where('paid_at', '<=', $toBound);
        }

        return $query->groupByRaw($groupExpr)
            ->orderByRaw($groupExpr)
            ->get()
            ->toArray();
    }

    /**
     * Get total revenue sum.
     * (Lấy tổng doanh thu)
     */
    public function getTotalRevenue(?string $from = null, ?string $to = null): float
    {
        $query = $this->model->newQuery()
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at');

        if ($from) {
            $query->whereDate('paid_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('paid_at', '<=', $to);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get detailed revenue report grouped by tour.
     * (Lấy báo cáo doanh thu chi tiết theo tour)
     */
    public function getRevenueDetailByTour(?string $from, ?string $to): array
    {
        // Use subquery to get unique paid booking IDs in the given period
        // to avoid double counting if multiple payments exist for one booking.
        $paidBookingIds = $this->model->newQuery()
            ->where('payment_status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at')
            ->when($from, fn ($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('paid_at', '<=', $to))
            ->distinct()
            ->pluck('booking_id');

        return BookingItem::query()
            ->join('tours', 'booking_items.tour_id', '=', 'tours.id')
            ->whereIn('booking_id', $paidBookingIds)
            ->selectRaw('
                tours.id as tour_id, 
                tours.name as tour_name, 
                COUNT(DISTINCT booking_items.booking_id) as booking_count, 
                SUM(booking_items.subtotal) as total_revenue
            ')
            ->groupBy('tours.id', 'tours.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->toArray();
    }

    /**
     * Normalize paid_at filter bounds. Date-only "to" uses end of day (inclusive).
     * (Chuẩn hóa các ngưỡng paid_at)
     */
    private function paidAtBounds(?string $from, ?string $to): array
    {
        $tz = config('app.timezone');
        $fromBound = null;
        $toBound = null;

        if ($from !== null && $from !== '') {
            $fromBound = $this->isDateOnlyString($from)
                ? Carbon::parse($from, $tz)->startOfDay()->toDateTimeString()
                : Carbon::parse($from, $tz)->toDateTimeString();
        }

        if ($to !== null && $to !== '') {
            $toBound = $this->isDateOnlyString($to)
                ? Carbon::parse($to, $tz)->endOfDay()->toDateTimeString()
                : Carbon::parse($to, $tz)->toDateTimeString();
        }

        return [$fromBound, $toBound];
    }

    private function isDateOnlyString(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value));
    }
}
