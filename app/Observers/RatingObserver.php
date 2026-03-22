<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RatingObserver
{
    /**
     * Handle the Rating "updated" event.
     */
    public function updated(Rating $rating): void
    {
        // Kiểm tra xem status có chuyển sang 'approved' từ 'pending' không
        if ($rating->isDirty('status') && $rating->status === 'approved' && $rating->getOriginal('status') === 'pending') {
            $this->handleApproval($rating);
        }
    }

    /**
     * Logic xử lý khi bài đánh giá được duyệt.
     */
    protected function handleApproval(Rating $rating): void
    {
        DB::transaction(function () use ($rating) {
            $location = $rating->location;
            $user = $rating->user;

            // 1. Cập nhật thống kê địa điểm
            $approvedRatings = $location->ratings()->where('status', 'approved')->get();
            $reviewCount = $approvedRatings->count();
            $avgRating = $approvedRatings->avg('score') ?? 0;

            $location->update([
                'review_count' => $reviewCount,
                'avg_rating' => $avgRating,
            ]);

            // 2. Trừ point của user theo logic (2 không ảnh, 3 có ảnh)
            $pointToDeduct = $rating->image_count > 0 ? 3 : 2;
            $balanceBefore = $user->point_balance;
            $user->decrement('point_balance', $pointToDeduct);
            $balanceAfter = $user->point_balance;

            // 3. Ghi lại lịch sử giao dịch point
            PointTransaction::create([
                'user_id' => $user->id,
                'transaction_code' => 'SPEND-'.strtoupper(Str::random(10)),
                'type' => 'spend',
                'amount' => -$pointToDeduct,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_id' => $rating->id,
                'reference_type' => 'rating',
                'description' => 'Trừ point cho bài đánh giá tại '.$location->name,
                'status' => 'completed',
                'created_at' => now(),
            ]);

            // 4. Gửi thông báo cho user
            Notification::create([
                'user_id' => $user->id,
                'type' => 'rating_approved',
                'title' => 'Bài đánh giá đã được duyệt',
                'content' => "Chúc mừng! Bài đánh giá của bạn tại {$location->name} đã được duyệt. Bạn đã bị trừ {$pointToDeduct} point.",
                'data' => [
                    'rating_id' => $rating->id,
                    'location_name' => $location->name,
                ],
                'is_read' => false,
                'created_at' => now(),
            ]);
        });
    }
}
