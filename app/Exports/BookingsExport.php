<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingsExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $bookings;

    public function __construct(Collection $bookings)
    {
        $this->bookings = $bookings;
    }

    public function collection(): Collection
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Booking Code',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Total Amount',
            'Final Amount',
            'Deposit Amount',
            'Payment Method',
            'Payment Status',
            'Booking Status',
            'Booked At',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->id,
            $booking->booking_code,
            $booking->customer_name,
            $booking->customer_email,
            $booking->customer_phone,
            $booking->total_amount,
            $booking->final_amount,
            $booking->deposit_amount,
            $booking->payment_method,
            $booking->payment_status,
            $booking->booking_status,
            $booking->booked_at ? $booking->booked_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
