<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;

class InvoicePdfService
{
    public function render(Booking $booking): string
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($this->generateHtml($booking));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    private function generateHtml(Booking $booking): string
    {
        $bookingCode = $this->e($booking->booking_code);
        $invoiceDate = now()->format('d/m/Y H:i');

        $customerName = $this->e($booking->customer_name ?: ($booking->user?->full_name ?? 'Khách hàng'));
        $customerPhone = $this->e($booking->customer_phone ?: ($booking->user?->phone ?? 'Chưa cập nhật'));
        $customerEmail = $this->e($booking->customer_email ?: ($booking->user?->email ?? 'Chưa cập nhật'));
        $customerAddress = $this->e($booking->customer_address ?: 'Chưa cập nhật');
        $customerNote = $this->e($booking->customer_note ?: 'Không có ghi chú');

        $bookedAt = $booking->booked_at ? $booking->booked_at->format('d/m/Y H:i') : 'Chưa cập nhật';
        $confirmedAt = $booking->confirmed_at ? $booking->confirmed_at->format('d/m/Y H:i') : 'Chưa xác nhận';
        $bookingStatus = $this->translateBookingStatus($booking->booking_status);
        $paymentStatus = $this->translatePaymentStatus($booking->payment_status);
        $paymentMethod = $this->translatePaymentMethod($booking->payment_method);

        $items = $this->bookingItems($booking);
        $tableRows = $this->renderRows($items);

        $totalAmount = $this->money($booking->total_amount);
        $discountAmount = $this->money($booking->discount_amount);
        $depositAmount = $this->money($booking->deposit_amount);
        $payableAmount = max(0, (float) ($booking->final_amount ?? $booking->total_amount ?? 0));
        $finalAmount = $this->money($payableAmount);
        $remainingAmount = $this->money(max(0, $payableAmount - (float) $booking->deposit_amount));

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hóa đơn {$bookingCode}</title>
    <style>
        @page { margin: 22px 24px; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
            background: #ffffff;
        }
        .invoice {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }
        .topbar {
            background: #ff385c;
            color: #ffffff;
            padding: 22px 26px;
        }
        .brand {
            float: left;
            width: 52%;
        }
        .brand-mark {
            display: inline-block;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #ffffff;
            color: #ff385c;
            text-align: center;
            line-height: 34px;
            font-weight: bold;
            margin-right: 10px;
            vertical-align: middle;
        }
        .brand-name {
            display: inline-block;
            vertical-align: middle;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: .2px;
        }
        .brand-subtitle {
            margin-top: 6px;
            color: #ffe4ea;
            font-size: 10px;
        }
        .invoice-title {
            float: right;
            width: 42%;
            text-align: right;
        }
        .invoice-title h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: .5px;
        }
        .invoice-title p {
            margin: 6px 0 0;
            color: #ffe4ea;
        }
        .clear { clear: both; }
        .content {
            padding: 24px 26px 22px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-bottom: 12px;
        }
        .meta-card {
            width: 50%;
            vertical-align: top;
            border: 1px solid #edf2f7;
            background: #f9fafb;
            border-radius: 12px;
            padding: 14px;
        }
        .meta-card.left { margin-right: 10px; }
        .section-title {
            color: #ff385c;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: .3px;
        }
        .line {
            margin-bottom: 6px;
        }
        .label {
            color: #6b7280;
            display: inline-block;
            width: 112px;
        }
        .value {
            color: #111827;
            font-weight: bold;
        }
        .status {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 9px;
            font-size: 10px;
            font-weight: bold;
            background: #dcfce7;
            color: #047857;
            border: 1px solid #bbf7d0;
        }
        .status.pending {
            background: #fff7ed;
            color: #c2410c;
            border-color: #fed7aa;
        }
        .status.cancelled, .status.failed {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .items th {
            background: #111827;
            color: #ffffff;
            font-size: 10px;
            padding: 10px 8px;
            border: 1px solid #111827;
        }
        .items td {
            padding: 10px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items tr:nth-child(even) td {
            background: #fcfcfd;
        }
        .muted {
            color: #6b7280;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-wrap {
            margin-top: 18px;
        }
        .note {
            float: left;
            width: 48%;
            border: 1px dashed #fda4af;
            background: #fff1f2;
            border-radius: 12px;
            padding: 12px;
            color: #4b5563;
        }
        .summary {
            float: right;
            width: 42%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 8px 0;
            border-bottom: 1px solid #edf2f7;
        }
        .summary .label-cell {
            color: #6b7280;
        }
        .summary .value-cell {
            text-align: right;
            font-weight: bold;
        }
        .summary .total td {
            border-bottom: 0;
            padding-top: 12px;
            font-size: 13px;
        }
        .summary .total .value-cell {
            color: #ff385c;
            font-size: 17px;
        }
        .signatures {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            color: #374151;
            font-weight: bold;
        }
        .signatures .hint {
            display: block;
            margin-top: 4px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 400;
        }
        .footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .footer strong {
            color: #ff385c;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="topbar">
            <div class="brand">
                <span class="brand-mark">D</span>
                <span class="brand-name">DaNangTrip</span>
                <div class="brand-subtitle">Khám phá Đà Nẵng như người bản địa</div>
            </div>
            <div class="invoice-title">
                <h1>HÓA ĐƠN ĐẶT TOUR</h1>
                <p>Mã đơn: <strong>{$bookingCode}</strong></p>
                <p>Ngày xuất: <strong>{$invoiceDate}</strong></p>
            </div>
            <div class="clear"></div>
        </div>

        <div class="content">
            <table class="meta-grid">
                <tr>
                    <td class="meta-card left">
                        <div class="section-title">THÔNG TIN KHÁCH HÀNG</div>
                        <div class="line"><span class="label">Họ và tên:</span> <span class="value">{$customerName}</span></div>
                        <div class="line"><span class="label">Email:</span> <span class="value">{$customerEmail}</span></div>
                        <div class="line"><span class="label">Số điện thoại:</span> <span class="value">{$customerPhone}</span></div>
                        <div class="line"><span class="label">Địa chỉ:</span> <span class="value">{$customerAddress}</span></div>
                    </td>
                    <td style="width: 14px;"></td>
                    <td class="meta-card">
                        <div class="section-title">THÔNG TIN ĐƠN HÀNG</div>
                        <div class="line"><span class="label">Ngày đặt:</span> <span class="value">{$bookedAt}</span></div>
                        <div class="line"><span class="label">Ngày xác nhận:</span> <span class="value">{$confirmedAt}</span></div>
                        <div class="line"><span class="label">Trạng thái đơn:</span> <span class="status">{$bookingStatus}</span></div>
                        <div class="line"><span class="label">Thanh toán:</span> <span class="status">{$paymentStatus}</span></div>
                        <div class="line"><span class="label">Phương thức:</span> <span class="value">{$paymentMethod}</span></div>
                    </td>
                </tr>
            </table>

            <div class="section-title">CHI TIẾT DỊCH VỤ</div>
            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 6%;" class="text-center">STT</th>
                        <th style="width: 39%;">TOUR / DỊCH VỤ</th>
                        <th style="width: 15%;" class="text-center">NGÀY ĐI</th>
                        <th style="width: 15%;" class="text-center">SỐ KHÁCH</th>
                        <th style="width: 12%;" class="text-right">ĐƠN GIÁ</th>
                        <th style="width: 13%;" class="text-right">THÀNH TIỀN</th>
                    </tr>
                </thead>
                <tbody>
                    {$tableRows}
                </tbody>
            </table>

            <div class="summary-wrap">
                <div class="note">
                    <strong>GHI CHÚ CỦA KHÁCH HÀNG</strong><br>
                    {$customerNote}<br><br>
                    <span class="muted">Hóa đơn được tạo tự động từ hệ thống DaNangTrip. Vui lòng giữ mã đơn để đối chiếu khi cần hỗ trợ.</span>
                </div>
                <table class="summary">
                    <tr>
                        <td class="label-cell">Tạm tính</td>
                        <td class="value-cell">{$totalAmount}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Giảm giá</td>
                        <td class="value-cell">- {$discountAmount}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Đã thanh toán / đặt cọc</td>
                        <td class="value-cell">{$depositAmount}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Còn lại</td>
                        <td class="value-cell">{$remainingAmount}</td>
                    </tr>
                    <tr class="total">
                        <td class="label-cell"><strong>Tổng thanh toán</strong></td>
                        <td class="value-cell">{$finalAmount}</td>
                    </tr>
                </table>
                <div class="clear"></div>
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        Khách hàng
                        <span class="hint">Ký và ghi rõ họ tên nếu cần đối chiếu</span>
                    </td>
                    <td>
                        DaNangTrip
                        <span class="hint">Hóa đơn điện tử tạo tự động</span>
                    </td>
                </tr>
            </table>

            <div class="footer">
                <strong>Cảm ơn quý khách đã sử dụng DaNangTrip.</strong><br>
                Hỗ trợ: info@danangtrip.com | Hotline: 1900 1800 | Đà Nẵng, Việt Nam
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function renderRows(Collection $items): string
    {
        if ($items->isEmpty()) {
            return '<tr><td colspan="6" class="text-center">Không có thông tin dịch vụ.</td></tr>';
        }

        return $items->map(function ($item, int $index): string {
            $name = $this->e($item->item_name ?: ($item->tour?->name ?? 'Tour du lịch'));
            $travelDate = $item->travel_date
                ? (is_string($item->travel_date) ? date('d/m/Y', strtotime($item->travel_date)) : $item->travel_date->format('d/m/Y'))
                : 'Chưa cập nhật';
            $adult = (int) $item->quantity_adult;
            $child = (int) $item->quantity_child;
            $infant = (int) $item->quantity_infant;
            $quantity = $adult + $child + $infant;
            $quantityText = "{$quantity} khách";
            $detailText = "Người lớn: {$adult}";

            if ($child > 0) {
                $detailText .= " | Trẻ em: {$child}";
            }

            if ($infant > 0) {
                $detailText .= " | Em bé: {$infant}";
            }

            $unitPrice = $adult > 0 ? $this->money($item->unit_price_adult) : $this->money($item->subtotal);
            $subtotal = $this->money($item->subtotal);
            $rowNumber = $index + 1;

            return <<<HTML
<tr>
    <td class="text-center">{$rowNumber}</td>
    <td>
        <strong>{$name}</strong><br>
        <span class="muted">{$detailText}</span>
    </td>
    <td class="text-center">{$travelDate}</td>
    <td class="text-center">{$quantityText}</td>
    <td class="text-right">{$unitPrice}</td>
    <td class="text-right"><strong>{$subtotal}</strong></td>
</tr>
HTML;
        })->implode('');
    }

    private function bookingItems(Booking $booking): Collection
    {
        $items = $booking->items ?? $booking->booking_items ?? collect();

        return $items instanceof Collection ? $items : collect($items);
    }

    private function translateBookingStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }

    private function translatePaymentStatus(string $status): string
    {
        return match ($status) {
            'pending', 'unpaid' => 'Chờ thanh toán',
            'success' => 'Thanh toán thành công',
            'failed' => 'Thanh toán thất bại',
            'refunded' => 'Đã hoàn tiền',
            'partially_paid' => 'Thanh toán một phần',
            default => $status,
        };
    }

    private function translatePaymentMethod(?string $method): string
    {
        if (! $method) {
            return 'Chưa cập nhật';
        }

        return match ($method) {
            'sepay', 'payos' => 'SePay VietQR',
            'vnpay' => 'VNPAY',
            'momo' => 'MoMo',
            'zalopay' => 'ZaloPay',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'credit_card' => 'Thẻ tín dụng',
            'cash' => 'Tiền mặt',
            'paypal' => 'PayPal',
            default => $method,
        };
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, ',', '.').' &#273;';
    }

    private function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
