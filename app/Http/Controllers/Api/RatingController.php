<?php

namespace App\Http\Controllers\Api;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Validations\RatingValidation;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RatingController
 * Handles API requests for user ratings.
 * (Xử lý các yêu cầu API cho đánh giá của người dùng)
 */
final class RatingController extends Controller
{
    public function __construct(
        protected RatingService $ratingService
    ) {}

    /**
     * Create a new rating.
     * (Tạo đánh giá mới)
     *
     * @param Request request
     */
    public function store(Request $request): JsonResponse
    {
        $validator = RatingValidation::validateStore($request);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $data = $validator->validated();
        $data['user_id'] = auth('api')->id();

        $result = $this->ratingService->createRating($data, $request);

        return $result['status'] === HttpStatusCode::CREATED->value
            ? $this->created($result['data'], $result['message'] ?? 'Rating created successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Update a rating of the authenticated user.
     * (Cập nhật đánh giá của chính mình)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = RatingValidation::validateUpdate($request, $id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $data = $validator->validated();
        $userId = auth('api')->id();

        $result = $this->ratingService->updateRating($userId, $id, $data, $request);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Rating updated successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Delete a rating of the authenticated user.
     * (Xóa đánh giá của chính mình)
     */
    public function destroy(int $id): JsonResponse
    {
        $validator = RatingValidation::validateDestroy($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $userId = auth('api')->id();

        $result = $this->ratingService->deleteRating($userId, $id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success(null, $result['message'] ?? 'Rating deleted successfully')
            : $this->error($result['message'], $result['status']);
    }

    /**
     * Mark a rating as helpful.
     * (Đánh dấu đánh giá hữu ích)
     */
    public function helpful(int $id): JsonResponse
    {
        $validator = RatingValidation::validateHelpful($id);
        if ($validator->fails()) {
            return $this->validation_error($validator->errors());
        }

        $result = $this->ratingService->markHelpful($id);

        return $result['status'] === HttpStatusCode::SUCCESS->value
            ? $this->success($result['data'], $result['message'] ?? 'Marked as helpful')
            : $this->error($result['message'], $result['status']);
    }
}
