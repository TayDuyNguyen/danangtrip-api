<?php

namespace App\Observers;

use App\Jobs\SendRatingApprovedNotification;
use App\Models\Rating;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;

/**
 * Observer for Rating model events.
 * (Observer xử lý các sự kiện của model Đánh giá)
 */
class RatingObserver
{
    protected LocationRepositoryInterface $locationRepository;

    protected RatingRepositoryInterface $ratingRepository;

    /**
     * Create a new observer instance.
     * (Khởi tạo một instance observer mới)
     */
    public function __construct(LocationRepositoryInterface $locationRepository, RatingRepositoryInterface $ratingRepository)
    {
        $this->locationRepository = $locationRepository;
        $this->ratingRepository = $ratingRepository;
    }

    /**
     * Handle the Rating "created" event.
     * (Xử lý sự kiện khi Đánh giá được tạo)
     */
    public function created(Rating $rating): void
    {
        if ($rating->status === 'approved') {
            $this->refreshLocationStats($rating->location_id);
            $this->notifyUserRatingApproved($rating);
        }
    }

    /**
     * Handle the Rating "updated" event.
     * (Xử lý sự kiện khi Đánh giá được cập nhật)
     */
    public function updated(Rating $rating): void
    {
        $wasApproved = $rating->getOriginal('status') === 'approved';
        $isApproved = $rating->status === 'approved';

        if (! $wasApproved && $isApproved) {
            // Mới được duyệt
            $this->refreshLocationStats($rating->location_id);
            $this->notifyUserRatingApproved($rating);
        } elseif ($wasApproved && $isApproved && $rating->isDirty('score')) {
            // Đã duyệt nhưng thay đổi điểm
            $this->refreshLocationStats($rating->location_id);
        } elseif ($wasApproved && ! $isApproved) {
            // Bị hủy duyệt / từ chối
            $this->refreshLocationStats($rating->location_id);
        }
    }

    /**
     * Handle the Rating "deleted" event.
     * (Xử lý sự kiện khi Đánh giá bị xóa)
     */
    public function deleted(Rating $rating): void
    {
        if ($rating->status === 'approved') {
            $this->refreshLocationStats($rating->location_id);
        }
    }

    /**
     * Handle the Rating "restored" event.
     * (Xử lý sự kiện khi Đánh giá được khôi phục)
     */
    public function restored(Rating $rating): void
    {
        if ($rating->status === 'approved') {
            $this->refreshLocationStats($rating->location_id);
        }
    }

    /**
     * Refresh location rating statistics.
     * (Cập nhật lại thống kê đánh giá của Địa điểm)
     */
    protected function refreshLocationStats(?int $locationId): void
    {
        if (! $locationId) {
            return;
        }

        // Use the repository to update stats internally (handles calculation, locking, and transaction)
        $this->locationRepository->updateStats($locationId);
    }

    /**
     * Dispatch job to notify user that their rating is approved.
     * (Gửi job thông báo cho người dùng rằng đánh giá của họ đã được duyệt)
     */
    protected function notifyUserRatingApproved(Rating $rating): void
    {
        SendRatingApprovedNotification::dispatch($rating->id);
    }
}
