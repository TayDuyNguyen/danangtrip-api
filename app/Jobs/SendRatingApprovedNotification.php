<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Rating;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job to send notification when a rating is approved.
 * (Job gửi thông báo khi một đánh giá được duyệt)
 */
class SendRatingApprovedNotification implements ShouldQueue
{
    use Queueable;

    public int $ratingId;

    /**
     * Create a new job instance.
     * (Khởi tạo một instance job mới)
     */
    public function __construct(int $ratingId)
    {
        $this->ratingId = $ratingId;
    }

    /**
     * Execute the job.
     * (Thực hiện job)
     */
    public function handle(): void
    {
        $rating = Rating::with(['location', 'user'])->find($this->ratingId);

        if (! $rating || $rating->status !== 'approved') {
            return;
        }

        $location = $rating->location;
        $user = $rating->user;

        if (! $location || ! $user) {
            return;
        }
        Notification::create([
            'user_id' => $user->id,
            'type' => 'rating_approved',
            'title' => 'Đánh giá của bạn đã được duyệt',
            'message' => "Đánh giá của bạn tại {$location->name} đã được duyệt.",
            'data' => [
                'rating_id' => $rating->id,
                'location_id' => $location->id,
                'location_name' => $location->name,
            ],
            'is_read' => false,
        ]);

    }
}
