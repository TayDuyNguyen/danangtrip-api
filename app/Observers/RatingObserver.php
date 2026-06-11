<?php

namespace App\Observers;

use App\Jobs\SendRatingApprovedNotification;
use App\Models\Rating;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;

/**
 * Observer for Rating model events.
 * (Observer xử lý các sự kiện của model Đánh giá)
 */
class RatingObserver
{
    protected LocationRepositoryInterface $locationRepository;

    protected RatingRepositoryInterface $ratingRepository;

    protected TourRepositoryInterface $tourRepository;

    /**
     * Create a new observer instance.
     * (Khởi tạo một instance observer mới)
     */
    public function __construct(
        LocationRepositoryInterface $locationRepository,
        RatingRepositoryInterface $ratingRepository,
        TourRepositoryInterface $tourRepository
    ) {
        $this->locationRepository = $locationRepository;
        $this->ratingRepository = $ratingRepository;
        $this->tourRepository = $tourRepository;
    }

    /**
     * Handle the Rating "created" event.
     * (Xử lý sự kiện khi Đánh giá được tạo)
     */
    public function created(Rating $rating): void
    {
        if ($rating->status === 'approved') {
            $this->refreshLocationStats($rating->location_id);
            $this->refreshTourStats($rating->tour_id);
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
            $this->refreshTourStats($rating->tour_id);
            $this->notifyUserRatingApproved($rating);
        } elseif ($wasApproved && $isApproved) {
            if ($rating->isDirty('score')) {
                // Đã duyệt nhưng thay đổi điểm
                $this->refreshLocationStats($rating->location_id);
                $this->refreshTourStats($rating->tour_id);
            }

            if ($rating->isDirty('comment') || $rating->isDirty('image_count')) {
                $this->notifyUserRatingApproved($rating);
            }
        } elseif ($wasApproved && ! $isApproved) {
            // Bị hủy duyệt / từ chối
            $this->refreshLocationStats($rating->location_id);
            $this->refreshTourStats($rating->tour_id);
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
            $this->refreshTourStats($rating->tour_id);
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
            $this->refreshTourStats($rating->tour_id);
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
     * Refresh tour rating statistics.
     * (Cập nhật lại thống kê đánh giá của Tour)
     */
    protected function refreshTourStats(?int $tourId): void
    {
        if (! $tourId) {
            return;
        }

        // Use the repository to update stats internally (handles calculation, locking, and transaction)
        $this->tourRepository->updateStats($tourId);
    }

    /**
     * Dispatch job to notify user that their rating is approved.
     * (Gửi job thông báo cho người dùng rằng đánh giá của họ đã được duyệt)
     */
    protected function notifyUserRatingApproved(Rating $rating): void
    {
        SendRatingApprovedNotification::dispatch($rating->id)->afterCommit();
    }
}
