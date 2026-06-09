<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;

class BookingPaymentConfirmedMail extends Mailable
{
    public function __construct(
        public readonly Booking $booking
    ) {}

    public function build(): self
    {
        $appName = (string) config('app.name', 'Da Nang Trip');
        $safeName = e($this->booking->customer_name ?: $this->booking->user?->full_name ?: 'quý khách');
        $safeBookingCode = e((string) $this->booking->booking_code);
        $safeAmount = e(number_format((float) ($this->booking->final_amount ?? $this->booking->total_amount), 0, ',', '.').' đ');
        $tourName = e((string) ($this->booking->items->first()?->tour?->name ?? $this->booking->items->first()?->item_name ?? 'Tour đã đặt'));
        $travelDate = $this->booking->items->first()?->travel_date;
        $safeTravelDate = e($travelDate ? $travelDate->format('d/m/Y') : 'Theo lịch đã chọn');
        $frontendUrl = rtrim((string) config('app.frontend_url', ''), '/');
        $detailUrl = $frontendUrl !== ''
            ? $frontendUrl.'/profile/bookings/'.$this->booking->booking_code
            : null;
        $actionButton = $detailUrl
            ? '<a href="'.e($detailUrl).'" style="display:inline-block;margin-top:22px;padding:13px 20px;border-radius:999px;background:#ff3d6e;color:#ffffff;text-decoration:none;font-size:14px;font-weight:800">Xem chi tiết đơn hàng</a>'
            : '';

        return $this
            ->subject("[{$appName}] Thanh toán thành công đơn {$this->booking->booking_code}")
            ->html(<<<HTML
                <!doctype html>
                <html lang="vi">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="margin:0;padding:0;background:#f6f8fb">
                <div style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f8fb;padding:32px 12px">
                        <tr>
                            <td align="center">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #e2e8f0">
                                    <tr>
                                        <td style="padding:30px 32px;background:#0f172a">
                                            <div style="display:inline-block;padding:7px 12px;border-radius:999px;background:rgba(255,61,110,.16);color:#ff9bb6;font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase">
                                                {$appName}
                                            </div>
                                            <h1 style="margin:20px 0 0;color:#ffffff;font-size:26px;line-height:1.25;font-weight:800">
                                                Thanh toán thành công
                                            </h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:30px 32px 34px;background:#ffffff">
                                            <p style="margin:0 0 16px;color:#475569;font-size:15px;line-height:1.7">
                                                Xin chào {$safeName},
                                            </p>
                                            <p style="margin:0 0 18px;color:#0f172a;font-size:16px;line-height:1.75">
                                                DanangTrip đã ghi nhận thanh toán thành công và xác nhận đơn đặt tour của bạn.
                                            </p>
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#f8fafc;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0">
                                                <tr>
                                                    <td style="padding:14px 16px;color:#64748b;font-size:13px">Mã đơn</td>
                                                    <td style="padding:14px 16px;color:#0f172a;font-size:14px;font-weight:800;text-align:right">{$safeBookingCode}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 16px;color:#64748b;font-size:13px;border-top:1px solid #e2e8f0">Tour</td>
                                                    <td style="padding:14px 16px;color:#0f172a;font-size:14px;font-weight:700;text-align:right;border-top:1px solid #e2e8f0">{$tourName}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 16px;color:#64748b;font-size:13px;border-top:1px solid #e2e8f0">Ngày đi</td>
                                                    <td style="padding:14px 16px;color:#0f172a;font-size:14px;font-weight:700;text-align:right;border-top:1px solid #e2e8f0">{$safeTravelDate}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 16px;color:#64748b;font-size:13px;border-top:1px solid #e2e8f0">Số tiền</td>
                                                    <td style="padding:14px 16px;color:#ff3d6e;font-size:16px;font-weight:900;text-align:right;border-top:1px solid #e2e8f0">{$safeAmount}</td>
                                                </tr>
                                            </table>
                                            {$actionButton}
                                            <p style="margin:26px 0 0;color:#94a3b8;font-size:12px;line-height:1.6">
                                                Email này được gửi tự động sau khi hệ thống xác nhận giao dịch thanh toán.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
                </body>
                </html>
                HTML);
    }
}
