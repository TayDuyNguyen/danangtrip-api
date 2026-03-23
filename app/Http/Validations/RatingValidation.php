<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class RatingValidation
 * Provides centralized validation logic for ratings endpoints.
 * (Cung cấp logic xác thực tập trung cho các API đánh giá)
 */
final class RatingValidation
{
    /**
     * Validate create rating request.
     * (Xác thực yêu cầu tạo đánh giá)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        $userId = auth('api')->id();

        return Validator::make(
            $request->all(),
            [
                'location_id' => [
                    'required',
                    'integer',
                    'exists:locations,id',
                    Rule::unique('ratings', 'location_id')->where(fn ($q) => $q->where('user_id', $userId)),
                ],
                'score' => 'required|integer|between:1,5',
                'comment' => 'sometimes|nullable|string',
                'images' => 'sometimes|array|max:5',
                'images.*' => 'file|image|max:5120',
            ],
            self::messages()
        );
    }

    /**
     * Validate update rating request.
     * (Xác thực yêu cầu cập nhật đánh giá)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:ratings,id',
                'score' => 'sometimes|integer|between:1,5',
                'comment' => 'sometimes|nullable|string',
                'images' => 'sometimes|array|max:5',
                'images.*' => 'file|image|max:5120',
            ],
            self::messages()
        );
    }

    /**
     * Validate delete rating request.
     * (Xác thực yêu cầu xóa đánh giá)
     */
    public static function validateDestroy(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:ratings,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate helpful request.
     * (Xác thực yêu cầu đánh dấu hữu ích)
     */
    public static function validateHelpful(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:ratings,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin ratings list request.
     * (Xác thực yêu cầu danh sách đánh giá cho admin)
     */
    public static function validateAdminIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'status' => 'sometimes|in:pending,approved,rejected',
                'location_id' => 'sometimes|integer|exists:locations,id',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate approve rating request.
     * (Xác thực yêu cầu duyệt đánh giá)
     */
    public static function validateApprove(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:ratings,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate reject rating request.
     * (Xác thực yêu cầu từ chối đánh giá)
     */
    public static function validateReject(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:ratings,id',
                'rejected_reason' => 'required|string|max:255',
            ],
            self::messages()
        );
    }

    private static function messages(): array
    {
        return [
            'location_id.required' => 'The location_id is required. (location_id là bắt buộc.)',
            'location_id.exists' => 'The location_id does not exist. (location_id không tồn tại.)',
            'location_id.unique' => 'You already rated this location. (Bạn đã đánh giá địa điểm này.)',
            'score.required' => 'The score is required. (Điểm đánh giá là bắt buộc.)',
            'score.between' => 'The score must be between 1 and 5. (Điểm đánh giá phải từ 1 đến 5.)',
            'images.array' => 'Images must be an array. (Images phải là một mảng.)',
            'images.max' => 'Images must not exceed 5 files. (Tối đa 5 ảnh.)',
            'images.*.image' => 'Each file must be an image. (Mỗi file phải là ảnh.)',
            'images.*.max' => 'Each image must not exceed 5MB. (Mỗi ảnh tối đa 5MB.)',
            'id.required' => 'The id is required. (id là bắt buộc.)',
            'id.exists' => 'The rating does not exist. (Đánh giá không tồn tại.)',
            'status.in' => 'The status is invalid. (Trạng thái không hợp lệ.)',
            'rejected_reason.required' => 'The rejected_reason is required. (Lý do từ chối là bắt buộc.)',
            'rejected_reason.max' => 'The rejected_reason must not exceed 255 characters. (Lý do từ chối tối đa 255 ký tự.)',
        ];
    }
}
