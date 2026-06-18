<?php

namespace App\Services;

use App\Mail\BookingPaymentConfirmedMail;
use App\Models\Booking;
use App\Models\Notification;
use App\Support\JsonColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingPaymentNotificationService
{
    public function __construct(
        protected BrevoMailService $brevoMailService
    ) {}

    public function sendPaymentConfirmedAfterCommit(int $bookingId): void
    {
        DB::afterCommit(function () use ($bookingId): void {
            try {
                $booking = Booking::query()
                    ->with(['user', 'items.tour'])
                    ->find($bookingId);

                if (! $booking) {
                    return;
                }

                $this->notifyUser($booking);

                $email = $booking->customer_email ?: $booking->user?->email;
                if (! $email) {
                    return;
                }

                $this->brevoMailService->sendMailable(
                    email: $email,
                    name: $booking->customer_name ?: $booking->user?->full_name,
                    mailable: new BookingPaymentConfirmedMail($booking),
                    context: [
                        'type' => 'booking_payment_confirmed',
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning('BOOKING_PAYMENT_CONFIRMED_MAIL failed.', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    private function notifyUser(Booking $booking): void
    {
        if (! $booking->user_id) {
            return;
        }

        $existsQuery = Notification::query()
            ->where('user_id', $booking->user_id)
            ->where('type', 'booking_payment_confirmed');

        JsonColumn::whereInt($existsQuery, 'data', 'booking_id', (int) $booking->id);

        if ($existsQuery->exists()) {
            return;
        }

        Notification::query()->create([
            'user_id' => $booking->user_id,
            'type' => 'booking_payment_confirmed',
            'title' => 'Thanh toán đơn tour thành công',
            'content' => "Đơn {$booking->booking_code} đã được xác nhận thanh toán. Bạn có thể xem chi tiết trong hồ sơ cá nhân.",
            'data' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'final_amount' => (float) $booking->final_amount,
            ],
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
