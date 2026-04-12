<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class PaymentsExport
 * (Xuất danh sách thanh toán ra Excel)
 */
final class PaymentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * PaymentsExport constructor.
     * (Hàm khởi tạo)
     */
    public function __construct(
        protected Collection $data
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * Define Excel headings.
     * (Định nghĩa tiêu đề Excel)
     */
    public function headings(): array
    {
        return [
            'ID',
            'Booking Code',
            'User',
            'Transaction Code',
            'Amount',
            'Payment Method',
            'Status',
            'Gateway',
            'Paid At',
            'Refunded At',
            'Created At',
        ];
    }

    /**
     * Map data to Excel rows.
     * (Ánh xạ dữ liệu vào các hàng Excel)
     *
     * @param  mixed  $payment
     */
    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->booking->booking_code ?? 'N/A',
            $payment->booking->user->name ?? 'N/A',
            $payment->transaction_code,
            $payment->amount,
            $payment->payment_method,
            $payment->payment_status,
            $payment->payment_gateway ?? 'N/A',
            $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i:s') : 'N/A',
            $payment->refunded_at ? $payment->refunded_at->format('Y-m-d H:i:s') : 'N/A',
            $payment->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
