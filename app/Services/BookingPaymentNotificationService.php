<?php

namespace App\Services;

use App\Mail\BookingPaymentConfirmedMail;
use App\Models\Booking;
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
}
