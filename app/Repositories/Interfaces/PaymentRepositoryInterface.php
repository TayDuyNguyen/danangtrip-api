<?php

namespace App\Repositories\Interfaces;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface PaymentRepositoryInterface
 * (Giao diện Repository cho Thanh toán)
 */
interface PaymentRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all payments with filters.
     * (Lấy danh sách thanh toán với bộ lọc)
     */
    public function getPayments(array $filters): LengthAwarePaginator;

    /**
     * Find payment by transaction code.
     * (Tìm kiếm thanh toán theo mã giao dịch)
     */
    public function findByTransactionCode(string $transactionCode): ?Payment;

    /**
     * Find payment by transaction code with row lock FOR UPDATE.
     * (Tìm thanh toán theo mã giao dịch và khóa hàng FOR UPDATE)
     */
    public function findByTransactionCodeForUpdate(string $transactionCode): ?Payment;

    /**
     * Find latest pending payment for a booking and lock the row.
     */
    public function findLatestPendingByBookingIdForUpdate(int $bookingId, ?Carbon $createdAfter = null): ?Payment;

    /**
     * Close pending checkout sessions older than the supplied cutoff.
     */
    public function markExpiredPendingPaymentsFailedByBookingId(int $bookingId, Carbon $cutoff): int;

    /**
     * Find a successful payment for a booking.
     */
    public function findSuccessfulByBookingId(int $bookingId): ?Payment;

    public function markPendingPaymentsFailedByBookingId(int $bookingId): int;

    /**
     * Get payments for export.
     * (Lấy danh sách thanh toán để xuất file)
     */
    public function getExportPayments(array $filters): Collection;

    /**
     * Get revenue grouped by period (day/week/month/year).
     * (Lấy doanh thu theo khoảng thời gian)
     */
    public function getRevenueByPeriod(string $period, ?string $from, ?string $to): array;

    /**
     * Get total revenue sum.
     * (Lấy tổng doanh thu)
     */
    public function getTotalRevenue(?string $from = null, ?string $to = null): float;

    /**
     * Get detailed revenue report grouped by tour.
     * (Lấy báo cáo doanh thu chi tiết theo tour)
     */
    public function getRevenueDetailByTour(?string $from, ?string $to): array;

    /**
     * Get successful payment totals grouped by gateway.
     *
     * @return array<int, array{gateway: string, revenue: float|string, count: int}>
     */
    public function getGatewayBreakdown(?string $from, ?string $to, ?string $gatewayFilter = null): array;

    /**
     * Get total refunded amount in a date range.
     */
    public function getTotalRefundedAmount(?string $from, ?string $to): float;
}
