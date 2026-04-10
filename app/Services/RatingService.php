<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Repositories\Interfaces\LocationRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\Interfaces\RatingImageRepositoryInterface;
use App\Repositories\Interfaces\RatingRepositoryInterface;
use App\Repositories\Interfaces\TourRepositoryInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        protected TourRepositoryInterface $tourRepository
    ) {}

    /**
     * Check if user has rated a location, tour, or booking.
     * (Kiểm tra xem người dùng đã đánh giá chưa)
     */
    public function checkRating(int $userId, array $params): array
    {
        try {
            $rating = $this->ratingRepository->checkUserRated($userId, $params);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => [
                    'has_rated' => (bool) $rating,
                    'rating' => $rating,
                ],
            ];
        } catch (\Exception $e) {
            Log::error($e);

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
            Log::error($e);

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
            $rating = DB::transaction(function () use ($data, $request) {
                $ratingData = [
                    'user_id' => $data['user_id'],
                    'score' => $data['score'],
                    'comment' => $data['comment'] ?? null,
                    'status' => 'approved',
                    'rejected_reason' => null,
                    'approved_by' => null,
                    'approved_at' => now(),
                ];

                if (isset($data['location_id'])) {
                    $ratingData['location_id'] = $data['location_id'];
                } elseif (isset($data['tour_id'])) {
                    $ratingData['tour_id'] = $data['tour_id'];
                }

                if (isset($data['booking_id'])) {
                    $ratingData['booking_id'] = $data['booking_id'];
                }

                $rating = $this->ratingRepository->create($ratingData);

                $imageUrls = $this->storeRatingImages($request, $rating->id);
                if (count($imageUrls) > 0) {
                    $this->ratingRepository->update($rating->id, [
                        'image_count' => count($imageUrls),
                        'point_cost' => 0,
                    ]);

                    $this->ratingImageRepository->createMany($rating->id, $imageUrls);
                }

                return $this->ratingRepository->with(['images', 'location', 'user'])->find($rating->id);
            });

            return [
                'status' => HttpStatusCode::CREATED->value,
                'data' => $rating,
                'message' => 'Rating created successfully',
            ];
        } catch (\Exception $e) {
            Log::error($e);

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

                    $this->ratingImageRepository->createMany($rating->id, $imageUrls);
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
            Log::error($e);

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
            Log::error($e);

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
    public function markHelpful(int $ratingId): array
    {
        try {
            $updated = $this->ratingRepository->incrementHelpfulIfApproved($ratingId);

            if (! $updated) {
                return [
                    'status' => HttpStatusCode::CONFLICT->value,
                    'message' => 'Rating is not approved',
                ];
            }

            $rating = $this->ratingRepository->with(['user', 'location', 'images'])->find($ratingId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $rating,
                'message' => 'Marked as helpful',
            ];
        } catch (\Exception $e) {
            Log::error($e);

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
            Log::error($e);

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

                // Notify user
                $this->notificationRepository->create([
                    'user_id' => $rating->user_id,
                    'type' => 'rating_approved',
                    'title' => 'Bài đánh giá được duyệt',
                    'content' => 'Bài đánh giá của bạn đã được quản trị viên phê duyệt thành công. Cảm ơn sự đóng góp của bạn!',
                    'data' => [
                        'rating_id' => $rating->id,
                        'score' => $rating->score,
                    ],
                    'is_read' => false,
                    'created_at' => now(),
                ]);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->ratingRepository->with(['user', 'location', 'tour', 'images', 'approver'])->find($rating->id),
                    'message' => 'Rating approved',
                ];
            });

            return $result;
        } catch (\Exception $e) {
            Log::error($e);

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
                $rating = $this->ratingRepository->findForUpdate($ratingId, ['user', 'location']);
                if (! $rating) {
                    return ['status' => HttpStatusCode::NOT_FOUND->value, 'message' => 'Rating not found'];
                }

                if ($rating->status !== 'pending') {
                    return ['status' => HttpStatusCode::CONFLICT->value, 'message' => 'Rating is not pending'];
                }

                $this->ratingRepository->update($ratingId, [
                    'status' => 'rejected',
                    'rejected_reason' => $data['rejected_reason'],
                    'approved_by' => $adminId,
                    'approved_at' => null,
                ]);

                $this->notificationRepository->create([
                    'user_id' => $rating->user_id,
                    'type' => 'rating_rejected',
                    'title' => 'Bài đánh giá bị từ chối',
                    'content' => "Bài đánh giá của bạn tại {$rating->location->name} bị từ chối. Lý do: {$data['rejected_reason']}.",
                    'data' => [
                        'rating_id' => $rating->id,
                        'location_name' => $rating->location->name,
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
            Log::error($e);

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
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to delete rating',
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
            Log::error($e);

            return [
                'status' => HttpStatusCode::INTERNAL_SERVER_ERROR->value,
                'message' => 'Failed to fetch export data',
            ];
        }
    }

    /**
     * Store rating images and return URLs.
     * (Lưu ảnh đánh giá và trả về danh sách URL)
     */
    private function storeRatingImages(Request $request, int $ratingId): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        $files = $request->file('images');
        if (! is_array($files)) {
            $files = [$files];
        }

        $urls = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('ratings/'.$ratingId, 'public');
            $urls[] = asset('storage/'.$path);
        }

        return array_slice($urls, 0, 5);
    }
}
