<?php

namespace App\Repositories\Eloquent;

use App\Enums\Pagination;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
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
        $query = $this->model->with('booking.user');

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
        return $this->model->where('transaction_code', $transactionCode)->first();
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
        $query = $this->model->with('booking.user');

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
}
