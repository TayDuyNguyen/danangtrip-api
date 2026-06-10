<?php

namespace App\Services;

use App\Models\Booking;

class InvoicePdfService
{
    public function render(Booking $booking): string
    {
        $html = $this->generateHtml($booking);

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);

        return $pdf->output();
    }

    private function generateHtml(Booking $booking): string
    {
        $bookingCode = htmlspecialchars($booking->booking_code);
        $invoiceDate = now()->format('d/m/Y H:i:s');

        $customerName = htmlspecialchars($booking->customer_name ?: ($booking->user?->full_name ?? 'Khách lẻ'));
        $customerPhone = htmlspecialchars($booking->customer_phone ?: ($booking->user?->phone ?? 'N/A'));
        $customerEmail = htmlspecialchars($booking->customer_email ?: ($booking->user?->email ?? 'N/A'));

        $bookedAt = $booking->booked_at ? $booking->booked_at->format('d/m/Y H:i') : 'N/A';
        $bookingStatus = $this->translateBookingStatus($booking->booking_status);
        $paymentStatus = $this->translatePaymentStatus($booking->payment_status);
        $paymentMethod = $this->translatePaymentMethod($booking->payment_method);

        $tableRows = '';
        $items = $booking->items ?? collect();
        foreach ($items as $index => $item) {
            $name = htmlspecialchars($item->item_name ?: ($item->tour?->name ?? 'Tour Du Lịch'));
            $quantity = ((int) $item->quantity_adult) + ((int) $item->quantity_child) + ((int) $item->quantity_infant);
            $travelDate = $item->travel_date ? (is_string($item->travel_date) ? substr($item->travel_date, 0, 10) : $item->travel_date->format('d/m/Y')) : 'N/A';
            $subtotal = $this->money($item->subtotal);

            $stt = $index + 1;
            $tableRows .= "<tr>
                <td class=\"text-center\">{$stt}</td>
                <td>{$name}</td>
                <td class=\"text-center\">{$travelDate}</td>
                <td class=\"text-center\">{$quantity}</td>
                <td class=\"text-right\">{$subtotal}</td>
            </tr>";
        }

        if ($items->isEmpty()) {
            $tableRows .= '<tr><td colspan="5" style="text-align: center;">Không tìm thấy thông tin dịch vụ.</td></tr>';
        }

        $totalAmount = $this->money($booking->total_amount);
        $discountAmount = $this->money($booking->discount_amount);
        $depositAmount = $this->money($booking->deposit_amount);
        $finalAmount = $this->money($booking->final_amount ?: $booking->total_amount);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hóa đơn đặt tour</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #222222;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header-stripe {
            height: 6px;
            background-color: #FF385C;
            margin-bottom: 25px;
        }
        .container {
            padding: 0 40px;
        }
        .invoice-header {
            margin-bottom: 30px;
        }
        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #FF385C;
            float: left;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #222222;
            float: right;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .divider {
            border-bottom: 1px solid #e5e7eb;
            margin: 15px 0;
        }
        .meta-info {
            font-size: 11px;
            color: #6A6A6A;
            margin-bottom: 30px;
        }
        .meta-left {
            float: left;
        }
        .meta-right {
            float: right;
            text-align: right;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-col {
            width: 48%;
            float: left;
        }
        .info-col.right {
            float: right;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #222222;
            border-bottom: 1px solid #ebebeb;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 6px;
            font-size: 11px;
        }
        .info-label {
            color: #6A6A6A;
            display: inline-block;
            width: 100px;
        }
        .info-value {
            font-weight: bold;
        }
        .table-section {
            margin-bottom: 35px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background-color: #f7f7f7;
            color: #222222;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #444444;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-section {
            float: right;
            width: 45%;
            margin-bottom: 40px;
        }
        .summary-row {
            margin-bottom: 8px;
            font-size: 11px;
        }
        .summary-label {
            float: left;
            color: #6A6A6A;
        }
        .summary-value {
            float: right;
            text-align: right;
            font-weight: bold;
        }
        .summary-row.total {
            border-top: 1px solid #cccccc;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 13px;
        }
        .summary-row.total .summary-label {
            color: #222222;
            font-weight: bold;
        }
        .summary-row.total .summary-value {
            color: #FF385C;
            font-weight: bold;
        }
        .footer {
            margin-top: 60px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            text-align: center;
        }
        .footer-thank {
            font-weight: bold;
            color: #FF385C;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .footer-sub {
            color: #6A6A6A;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header-stripe"></div>
    <div class="container">
        <div class="invoice-header">
            <div class="brand">DA NANG TRIP</div>
            <div class="title">HÓA ĐƠN THANH TOÁN</div>
            <div class="clear"></div>
        </div>
        
        <div class="divider"></div>
        
        <div class="meta-info">
            <div class="meta-left">
                Mã hóa đơn: <strong>{$bookingCode}</strong>
            </div>
            <div class="meta-right">
                Ngày xuất: <strong>{$invoiceDate}</strong>
            </div>
            <div class="clear"></div>
        </div>
        
        <div class="info-section">
            <div class="info-col">
                <div class="section-title">Thông tin khách hàng</div>
                <div class="info-row">
                    <span class="info-label">Họ tên:</span>
                    <span class="info-value">{$customerName}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Điện thoại:</span>
                    <span class="info-value">{$customerPhone}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{$customerEmail}</span>
                </div>
            </div>
            
            <div class="info-col right">
                <div class="section-title">Chi tiết đơn đặt</div>
                <div class="info-row">
                    <span class="info-label">Ngày đặt:</span>
                    <span class="info-value">{$bookedAt}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái đơn:</span>
                    <span class="info-value">{$bookingStatus}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thanh toán:</span>
                    <span class="info-value">{$paymentStatus}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phương thức TT:</span>
                    <span class="info-value">{$paymentMethod}</span>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        
        <div class="table-section">
            <div class="section-title">Danh sách dịch vụ</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;" class="text-center">STT</th>
                        <th style="width: 47%;">Tên tour / Dịch vụ</th>
                        <th style="width: 20%;" class="text-center">Ngày khởi hành</th>
                        <th style="width: 10%;" class="text-center">Số lượng</th>
                        <th style="width: 15%;" class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    {$tableRows}
                </tbody>
            </table>
        </div>
        
        <div class="summary-section">
            <div class="summary-row">
                <span class="summary-label">Tổng cộng:</span>
                <span class="summary-value">{$totalAmount} VND</span>
                <div class="clear"></div>
            </div>
            <div class="summary-row">
                <span class="summary-label">Giảm giá:</span>
                <span class="summary-value">-{$discountAmount} VND</span>
                <div class="clear"></div>
            </div>
            <div class="summary-row">
                <span class="summary-label">Đặt cọc:</span>
                <span class="summary-value">{$depositAmount} VND</span>
                <div class="clear"></div>
            </div>
            <div class="summary-row total">
                <span class="summary-label">Thành tiền thực tế:</span>
                <span class="summary-value">{$finalAmount} VND</span>
                <div class="clear"></div>
            </div>
        </div>
        <div class="clear"></div>
        
        <div class="footer">
            <div class="footer-thank">Xin cảm ơn quý khách đã tin tưởng và sử dụng DaNangTrip!</div>
            <div class="footer-sub">Hệ thống đặt tour du lịch Đà Nẵng trực tuyến hàng đầu</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function translateBookingStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ thanh toán',
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
            'success' => 'Thành công',
            'failed' => 'Thất bại',
            'refunded' => 'Đã hoàn tiền',
            'partially_paid' => 'Thanh toán một phần',
            default => $status,
        };
    }

    private function translatePaymentMethod(?string $method): string
    {
        if (! $method) {
            return 'N/A';
        }

        return match ($method) {
            'sepay' => 'SePay VietQR',
            'vnpay' => 'VNPAY',
            'momo' => 'MoMo',
            'zalopay' => 'ZaloPay',
            'bank_transfer' => 'Chuyển khoản',
            'credit_card' => 'Thẻ tín dụng',
            'cash' => 'Tiền mặt',
            'paypal' => 'PayPal',
            'payos' => 'PayOS VietQR',
            default => $method,
        };
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }
}
