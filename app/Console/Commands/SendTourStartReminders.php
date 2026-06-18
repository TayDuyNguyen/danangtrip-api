<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Notification;
use App\Support\JsonColumn;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class SendTourStartReminders extends Command
{
    protected $signature = 'bookings:send-tour-reminders
        {--date= : Ngày khởi hành cần nhắc theo định dạng Y-m-d, mặc định là ngày mai}
        {--dry-run : Chỉ kiểm tra số thông báo sẽ tạo, không ghi database}';

    protected $description = 'Send in-app reminders to users before their confirmed tour starts.';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : now()->addDay()->toDateString();

        $bookings = Booking::query()
            ->with(['user', 'items.tour'])
            ->whereNotNull('user_id')
            ->where('booking_status', BookingStatus::CONFIRMED->value)
            ->where('payment_status', PaymentStatus::SUCCESS->value)
            ->whereHas('items', function ($query) use ($targetDate) {
                $query->whereDate('travel_date', $targetDate);
            })
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($bookings as $booking) {
            $item = $booking->items->first(
                fn ($bookingItem) => $bookingItem->travel_date?->toDateString() === $targetDate
            );
            $tour = $item?->tour;
            $tourName = $tour?->name ?? $item?->item_name ?? 'tour của bạn';
            $startTime = $tour?->start_time ?: 'theo lịch đã xác nhận';

            $existsQuery = Notification::query()
                ->where('user_id', $booking->user_id)
                ->where('type', 'tour_start_reminder');

            JsonColumn::whereInt($existsQuery, 'data', 'booking_id', (int) $booking->id);
            JsonColumn::whereText($existsQuery, 'data', 'travel_date', $targetDate);

            if ($existsQuery->exists()) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $created++;

                continue;
            }

            Notification::query()->create([
                'user_id' => $booking->user_id,
                'type' => 'tour_start_reminder',
                'title' => 'Tour của bạn sắp khởi hành',
                'content' => "Tour {$tourName} sẽ khởi hành vào {$targetDate} lúc {$startTime}. Vui lòng kiểm tra lịch trình và điểm hẹn trước khi đi.",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'tour_id' => $tour?->id,
                    'tour_name' => $tourName,
                    'travel_date' => $targetDate,
                    'start_time' => $startTime,
                ],
                'is_read' => false,
                'created_at' => now(),
            ]);

            $created++;
        }

        $this->info("Tour reminders checked for {$targetDate}. Created: {$created}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
