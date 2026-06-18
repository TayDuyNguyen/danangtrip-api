<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\Rating;
use App\Models\RatingHelpfulVote;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\RatingImageRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class RatingService
 * Handles business logic related to ratings.
 * (Xử lý logic nghiệp vụ liên quan đến đánh giá)
 */
final class RatingService
{
    /**
     * RatingService constructor.
     * (Khởi tạo RatingService)
     */
    public function __construct(
        protected RatingRepositoryInterface $ratingRepository,
        protected RatingImageRepositoryInterface $ratingImageRepository,
        protected NotificationRepositoryInterface $notificationRepository,
        protected LocationRepositoryInterface $locationRepository,
        protected TourRepositoryInterface $tourRepository,
        protected PointService $pointService,
        protected UploadService $uploadService
    ) {}

    /**
     * Check if user has rated a location, tour, or booking.
     * (Kiểm tra xem người dùng đã đánh giá chưa)
     */
    public function checkRating(int $userId, array $params): array
    {
        try {
            $rating = $this->ratingRepository->checkUserRated($userId, $params);

            $canRate = true;
            $message = null;

            if (isset($params['tour_id'])) {
                $hasBooking = DB::table('bookings')
                    ->join('booking_items', 'bookings.id', '=', 'booking_items.booking_id')
                    ->where('bookings.user_id', $userId)
                    ->where('booking_items.tour_id', $params['tour_id'])
                    ->whereIn('bookings.booking_status', ['completed', 'confirmed'])
                    ->exists();

                if (! $hasBooking) {
                    $canRate = false;
                    $message = 'Bạn phải đặt tour này và hoàn thành chuyến đi mới có thể đánh giá.';
                }
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'has_rated' => (bool) $rating,
                    'has_public_rating' => $rating?->status === 'approved',
                    'can_rate' => $canRate,
                    'message' => $message,
                    'rating' => $rating,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Check rating error: '.$e->getMessage(), [
                'exception' => $e,
                'userId' => $userId,
                'params' => $params,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to check rating',
            ];
        }
    }

    /**
     * Get images for a rating.
     * (Lấy danh sách ảnh của đánh giá)
     */
    public function getImages(int $ratingId): array
    {
        try {
            $rating = $this->ratingRepository->find($ratingId);
            if (! $rating) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
            }

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $rating->images()->get(),
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get images',
            ];
        }
    }

    /**
     * Create a new rating.
     * (Tạo đánh giá mới)
     */
    public function createRating(array $data, Request $request): array
    {
        try {
            if (isset($data['tour_id'])) {
                $hasBooking = DB::table('bookings')
                    ->join('booking_items', 'bookings.id', '=', 'booking_items.booking_id')
                    ->where('bookings.user_id', $data['user_id'])
                    ->where('booking_items.tour_id', $data['tour_id'])
                    ->whereIn('bookings.booking_status', ['completed', 'confirmed'])
                    ->exists();

                if (! $hasBooking) {
                    return [
                        'status' => HttpStatusCode::FORBIDDEN->value,
                        'message' => 'Bạn phải đặt tour này và hoàn thành chuyến đi mới có thể đánh giá.',
                    ];
                }
            }

            $rating = DB::transaction(function () use ($data) {
                $ratingData = [
                    'user_id' => $data['user_id'],
                    'score' => $data['score'],
                    'comment' => $data['comment'] ?? null,
                    'status' => 'approved',
                    'rejected_reason' => null,
                    'approved_by' => null,
                    'approved_at' => now(),
                    'is_new' => true,
                ];

                if (isset($data['location_id'])) {
                    $ratingData['location_id'] = $data['location_id'];
                } elseif (isset($data['tour_id'])) {
                    $ratingData['tour_id'] = $data['tour_id'];
                }

                if (isset($data['booking_id'])) {
                    $ratingData['booking_id'] = $data['booking_id'];
                }

                return $this->ratingRepository->create($ratingData);
            });

            $message = 'Rating created successfully';
            if ($request->hasFile('images')) {
                $imageUrls = $this->storeRatingImages($request, $rating->id);
                if (count($imageUrls) > 0) {
                    $this->ratingRepository->update($rating->id, [
                        'image_count' => count($imageUrls),
                    ]);
                    $this->ratingImageRepository->createMany($rating->id, $imageUrls);
                } else {
                    $message = 'Rating created successfully. Some images could not be uploaded.';
                    Log::warning('RATING_IMAGE_UPLOAD_SKIPPED', [
                        'rating_id' => $rating->id,
                        'user_id' => $data['user_id'],
                    ]);
                }
            }

            $rating = $this->ratingRepository->with(['images', 'location', 'user'])->find($rating->id);

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $rating,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            Log::error('RATING_CREATE_FAILED', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'data' => $data,
            ]);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to create rating',
            ];
        }
    }

    /**
     * Update a rating of the authenticated user.
     * (Cập nhật đánh giá của chính mình)
     */
    public function updateRating(int $userId, int $ratingId, array $data, Request $request): array
    {
        try {
            $result = DB::transaction(function () use ($userId, $ratingId, $data, $request) {
                $rating = $this->ratingRepository->find($ratingId);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ((int) $rating->user_id !== (int) $userId) {
                    return ['status' => HttpStatusCode::FORBIDDEN->value, 'message' => 'Forbidden'];
                }

                $wasApproved = $rating->status === 'approved';

                $update = [];
                if (array_key_exists('score', $data)) {
                    $update['score'] = $data['score'];
                }
                if (array_key_exists('comment', $data)) {
                    $update['comment'] = $data['comment'];
                }

                if (count($update) > 0) {
                    $update['status'] = 'approved';
                    $update['rejected_reason'] = null;
                    $update['approved_by'] = null;
                    $update['approved_at'] = now();
                    $this->ratingRepository->update($ratingId, $update);
                }

                if ($request->hasFile('images')) {
                    $this->ratingImageRepository->deleteByRatingId($rating->id);

                    $imageUrls = $this->storeRatingImages($request, $rating->id);
                    $this->ratingRepository->update($rating->id, [
                        'image_count' => count($imageUrls),
                    ]);

                    if (count($imageUrls) > 0) {
                        $this->ratingImageRepository->createMany($rating->id, $imageUrls);
                    }
                }

                if ($wasApproved) {
                    if ($rating->location_id) {
                        $this->locationRepository->updateStats((int) $rating->location_id);
                    } elseif ($rating->tour_id) {
                        $this->tourRepository->updateStats((int) $rating->tour_id);
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->ratingRepository->with(['images', 'location', 'user'])->find($rating->id),
                ];
            });

            return $result;
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to update rating',
            ];
        }
    }

    /**
     * Delete a rating of the authenticated user.
     * (Xóa đánh giá của chính mình)
     */
    public function deleteRating(int $userId, int $ratingId): array
    {
        try {
            $result = DB::transaction(function () use ($userId, $ratingId) {
                $rating = $this->ratingRepository->find($ratingId);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ((int) $rating->user_id !== (int) $userId) {
                    return ['status' => HttpStatusCode::FORBIDDEN->value, 'message' => 'Forbidden'];
                }

                $locationId = (int) $rating->location_id;
                $wasApproved = $rating->status === 'approved';

                $this->ratingRepository->delete($rating->id);

                if ($wasApproved) {
                    if ($rating->location_id) {
                        $this->locationRepository->updateStats((int) $rating->location_id);
                    } elseif ($rating->tour_id) {
                        $this->tourRepository->updateStats((int) $rating->tour_id);
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Rating deleted successfully',
                ];
            });

            return $result;
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete rating',
            ];
        }
    }

    /**
     * Mark a rating as helpful.
     * (Đánh dấu đánh giá hữu ích)
     */
    public function markHelpful(int $userId, int $ratingId): array
    {
        try {
            return DB::transaction(function () use ($userId, $ratingId): array {
                $rating = $this->ratingRepository->findForUpdate($ratingId, ['user', 'location', 'tour', 'images']);

                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ($rating->status !== 'approved') {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'Rating is not approved'];
                }

                if ((int) $rating->user_id === $userId) {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'You cannot mark your own rating as helpful'];
                }

                $alreadyVoted = RatingHelpfulVote::query()
                    ->where('rating_id', $rating->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($alreadyVoted) {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'You already marked this rating as helpful'];
                }

                $vote = RatingHelpfulVote::query()->create([
                    'rating_id' => $rating->id,
                    'user_id' => $userId,
                ]);

                $rating->increment('helpful_count');
                $rating->refresh()->load(['user', 'location', 'tour', 'images']);

                $targetName = $rating->location?->name ?? $rating->tour?->name ?? 'nội dung bạn đánh giá';

                $this->pointService->awardPoints(
                    (int) $rating->user_id,
                    'content_helpful_received',
                    'rating_helpful_vote',
                    $vote->id,
                    "Đánh giá được người khác đánh dấu hữu ích: {$targetName}",
                    true
                );

                $this->awardHelpfulMilestoneIfReached($rating);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $rating,
                    'message' => 'Marked as helpful',
                ];
            });
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark helpful',
            ];
        }
    }

    /**
     * Admin: list ratings for moderation.
     * (Admin: danh sách đánh giá để kiểm duyệt)
     */
    public function adminList(array $filters): array
    {
        try {
            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->ratingRepository->paginateForAdmin($filters),
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to get ratings',
            ];
        }
    }

    /**
     * Admin: approve a rating.
     * (Admin: duyệt đánh giá)
     */
    public function approve(int $adminId, int $ratingId): array
    {
        try {
            $result = DB::transaction(function () use ($adminId, $ratingId) {
                $rating = $this->ratingRepository->findForUpdate($ratingId);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ($rating->status !== 'pending') {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'Rating is not pending'];
                }

                $this->ratingRepository->update($ratingId, [
                    'status' => 'approved',
                    'approved_by' => $adminId,
                    'approved_at' => now(),
                    'rejected_reason' => null,
                ]);

                // Update scores and review counts
                if ($rating->location_id) {
                    $this->locationRepository->updateStats((int) $rating->location_id);
                } elseif ($rating->tour_id) {
                    $this->tourRepository->updateStats((int) $rating->tour_id);
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->ratingRepository->with(['user', 'location', 'tour', 'images', 'approver'])->find($rating->id),
                    'message' => 'Rating approved',
                ];
            });

            return $result;
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to approve rating',
            ];
        }
    }

    /**
     * Admin: reject a rating.
     * (Admin: từ chối đánh giá)
     */
    public function reject(int $adminId, int $ratingId, array $data): array
    {
        try {
            $result = DB::transaction(function () use ($adminId, $ratingId, $data) {
                $rating = $this->ratingRepository->findForUpdate($ratingId, ['user', 'location', 'tour']);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ($rating->status !== 'pending' && $rating->status !== 'approved') {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'Rating is not pending or approved'];
                }

                $wasApproved = $rating->status === 'approved';

                $this->ratingRepository->update($ratingId, [
                    'status' => 'rejected',
                    'rejected_reason' => $data['rejected_reason'],
                    'approved_by' => $adminId,
                    'approved_at' => null,
                ]);

                if ($wasApproved) {
                    if ($rating->location_id) {
                        $this->locationRepository->updateStats((int) $rating->location_id);
                    } elseif ($rating->tour_id) {
                        $this->tourRepository->updateStats((int) $rating->tour_id);
                    }
                }

                $targetName = $rating->location?->name ?? $rating->tour?->name ?? 'item';

                $this->notificationRepository->create([
                    'user_id' => $rating->user_id,
                    'type' => 'rating_rejected',
                    'title' => 'Bài đánh giá bị từ chối',
                    'content' => "Bài đánh giá của bạn tại {$targetName} bị từ chối. Lý do: {$data['rejected_reason']}.",
                    'data' => [
                        'rating_id' => $rating->id,
                        'target_name' => $targetName,
                    ],
                    'is_read' => false,
                    'created_at' => now(),
                ]);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->ratingRepository->with(['user', 'location', 'tour', 'images', 'approver'])->find($rating->id),
                    'message' => 'Rating rejected',
                ];
            });

            return $result;
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to reject rating',
            ];
        }
    }

    /**
     * Admin: delete a rating.
     * (Admin: xóa đánh giá)
     */
    public function adminDelete(int $id): array
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $rating = $this->ratingRepository->find($id);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                $locationId = $rating->location_id ? (int) $rating->location_id : null;
                $tourId = $rating->tour_id ? (int) $rating->tour_id : null;
                $wasApproved = $rating->status === 'approved';

                $this->ratingRepository->delete($id);

                if ($wasApproved) {
                    if ($locationId) {
                        $this->locationRepository->updateStats($locationId);
                    } elseif ($tourId) {
                        $this->tourRepository->updateStats($tourId);
                    }
                }

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Rating deleted successfully',
                ];
            });

            return $result;
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete rating',
            ];
        }
    }

    /**
     * Admin: mark a rating as viewed (set is_new = 0).
     * Does NOT affect public display status.
     * (Admin: đánh dấu đánh giá là đã xem - không ảnh hưởng hiển thị công khai)
     */
    public function markViewed(int $ratingId): array
    {
        try {
            $rating = $this->ratingRepository->find($ratingId);
            if (! $rating) {
                return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
            }

            if (! $rating->is_new) {
                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Already viewed',
                    'data' => $rating,
                ];
            }

            $this->ratingRepository->update($ratingId, ['is_new' => false]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->ratingRepository->find($ratingId),
                'message' => 'Rating marked as viewed',
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to mark rating as viewed',
            ];
        }
    }

    /**
     * Admin: Collection for export.
     * (Admin: Phục vụ xuất file excel)
     */
    public function exportRatings(array $filters): array
    {
        try {
            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->ratingRepository->searchForExport($filters),
            ];
        } catch (\Exception $e) {

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to fetch export data',
            ];
        }
    }

    /**
     * Store rating images to Cloudinary and return URLs.
     * (Lưu ảnh đánh giá lên Cloudinary và trả về danh sách URL)
     */
    private function storeRatingImages(Request $request, int $ratingId): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $files = $request->file('images');
        if (! is_array($files)) {
            $files = [$files];
        }

        $files = array_slice($files, 0, 5);

        $uploadResult = $this->uploadService->uploadImages($files, 'ratings/'.$ratingId);

        if ($uploadResult['status'] !== HttpStatusCode::CREATED->value) {
            Log::warning('RATING_IMAGE_UPLOAD_FAILED', [
                'rating_id' => $ratingId,
                'message' => $uploadResult['message'] ?? null,
            ]);

            return [];
        }

        $urls = [];
        if (isset($uploadResult['data']['items']) && is_array($uploadResult['data']['items'])) {
            foreach ($uploadResult['data']['items'] as $item) {
                if (isset($item['url'])) {
                    $urls[] = $item['url'];
                }
            }
        }

        return $urls;
    }

    private function awardHelpfulMilestoneIfReached(Rating $rating): void
    {
        $helpfulCount = (int) ($rating->helpful_count ?? 0);
        $targetName = $rating->location?->name ?? $rating->tour?->name ?? 'nội dung bạn đánh giá';

        $milestones = [
            5 => 'content_helpful_milestone_5',
            10 => 'content_helpful_milestone_10',
        ];

        if (! isset($milestones[$helpfulCount])) {
            return;
        }

        $this->pointService->awardPoints(
            (int) $rating->user_id,
            $milestones[$helpfulCount],
            'rating_helpful_milestone_'.$helpfulCount,
            (int) $rating->id,
            "Đánh giá đạt {$helpfulCount} lượt hữu ích: {$targetName}",
            true
        );
    }
}
