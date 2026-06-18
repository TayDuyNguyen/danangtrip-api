<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Rating;
use App\Services\PointService;
use App\Support\JsonColumn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

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
    public function handle(PointService $pointService): void
    {
        try {
            $this->sendNotification($pointService);
        } catch (Throwable $e) {
            Log::error('RATING_APPROVED_NOTIFICATION_FAILED', [
                'rating_id' => $this->ratingId,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }
    }

    private function sendNotification(PointService $pointService): void
    {
        $rating = Rating::with(['location', 'tour', 'user', 'images'])->find($this->ratingId);

        if (! $rating || $rating->status !== 'approved') {
            return;
        }

        $user = $rating->user;

        if (! $user) {
            return;
        }

        $targetName = $rating->location?->name ?? $rating->tour?->name ?? 'nội dung bạn đánh giá';

        $normalizedComment = preg_replace('/\s+/u', ' ', trim((string) $rating->comment)) ?? '';
        $hasQualityComment = mb_strlen($normalizedComment) >= 50;

        $pointService->awardPoints(
            $user->id,
            'review_quality',
            'rating',
            $rating->id,
            "Đánh giá được duyệt: {$targetName}",
            true
        );

        if ((int) $rating->image_count > 0 || $rating->images->isNotEmpty()) {
            $pointService->awardPoints(
                $user->id,
                'review_with_image',
                'rating_image',
                $rating->id,
                "Đánh giá kèm ảnh được duyệt: {$targetName}",
                true
            );
        }

        $existsQuery = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', 'rating_approved');

        JsonColumn::whereInt($existsQuery, 'data', 'rating_id', (int) $rating->id);

        if ($existsQuery->exists()) {
            return;
        }

        Notification::create([
            'user_id' => $user->id,
            'type' => 'rating_approved',
            'title' => 'Đánh giá của bạn đã được duyệt',
            'content' => "Đánh giá của bạn tại {$targetName} đã được duyệt và hệ thống đã cộng điểm thưởng cho bạn.",
            'data' => [
                'rating_id' => $rating->id,
                'location_id' => $rating->location_id,
                'tour_id' => $rating->tour_id,
                'target_name' => $targetName,
            ],
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
