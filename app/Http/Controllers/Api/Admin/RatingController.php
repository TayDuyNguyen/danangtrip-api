<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Validations\RatingValidation;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RatingController
 * Handles admin API requests for ratings moderation.
 * (Xử lý các yêu cầu API quản trị cho kiểm duyệt đánh giá)
 */
final class RatingController extends Controller
{
    public function __construct(
        protected RatingService $ratingService
    ) {}

    /**
     * List ratings for moderation.
     * (Danh sách đánh giá chờ duyệt / tất cả)
     */
    public function index(Request $request): JsonResponse
    {
        $validator = RatingValidation::validateAdminIndex($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->ratingService->adminList($validator->validated());

        return $result['status'] === 200
            ? $this->success($result['data'])
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Approve a rating.
     * (Duyệt đánh giá)
     */
    public function approve(int $id): JsonResponse
    {
        $validator = RatingValidation::validateApprove($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $adminId = auth('api')->id();
        $result = $this->ratingService->approve($adminId, $id);

        return $result['status'] === 200
            ? $this->success($result['data'], $result['message'] ?? 'Rating approved')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Reject a rating with reason.
     * (Từ chối đánh giá kèm lý do)
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = RatingValidation::validateReject($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $adminId = auth('api')->id();
        $result = $this->ratingService->reject($adminId, $id, $validator->validated());

        return $result['status'] === 200
            ? $this->success($result['data'], $result['message'] ?? 'Rating rejected')
            : $this->error($result['message'], $result['status']);
    }
}
