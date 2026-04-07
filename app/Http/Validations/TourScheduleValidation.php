<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class TourScheduleValidation
 * Provides centralized validation logic for tour schedule management.
 * (Cung cấp logic xác thực tập trung cho quản lý lịch khởi hành tour)
 */
final class TourScheduleValidation
{
    /**
     * Validate index tour schedule request.
     * (Xác thực yêu cầu danh sách lịch khởi hành)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'tour_id' => 'sometimes|integer|exists:tours,id',
                'status' => 'sometimes|string|in:available,full,cancelled',
                'from' => 'sometimes|date|date_format:Y-m-d',
                'to' => 'sometimes|date|date_format:Y-m-d|after_or_equal:from',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate show tour schedule request.
     * (Xác thực yêu cầu chi tiết lịch khởi hành)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:tour_schedules,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate store tour schedule request.
     * (Xác thực yêu cầu thêm lịch khởi hành)
     */
    public static function validateStore(int $tourId, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['tour_id' => $tourId]),
            [
                'tour_id' => 'required|integer|exists:tours,id',
                'start_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
                'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
                'max_people' => 'required|integer|min:1',
                'price_adult' => 'sometimes|nullable|numeric|min:0',
                'price_child' => 'sometimes|nullable|numeric|min:0',
                'price_infant' => 'sometimes|nullable|numeric|min:0',
                'status' => 'sometimes|string|in:available,full,cancelled',
            ],
            self::messages()
        );
    }

    /**
     * Validate update tour schedule request.
     * (Xác thực yêu cầu cập nhật lịch khởi hành)
     */
    public static function validateUpdate(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tour_schedules,id',
                'start_date' => 'sometimes|date|date_format:Y-m-d|after_or_equal:today',
                'end_date' => 'sometimes|date|date_format:Y-m-d|after_or_equal:start_date',
                'max_people' => 'sometimes|integer|min:1',
                'price_adult' => 'sometimes|nullable|numeric|min:0',
                'price_child' => 'sometimes|nullable|numeric|min:0',
                'price_infant' => 'sometimes|nullable|numeric|min:0',
                'status' => 'sometimes|string|in:available,full,cancelled',
            ],
            self::messages()
        );
    }

    /**
     * Validate update status request.
     * (Xác thực yêu cầu đổi trạng thái)
     */
    public static function validateUpdateStatus(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tour_schedules,id',
                'status' => 'required|string|in:available,full,cancelled',
            ],
            self::messages()
        );
    }

    /**
     * Get custom error messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    protected static function messages(): array
    {
        return [
            'id.required' => 'Schedule ID is required.',
            'id.exists' => 'The selected schedule does not exist.',
            'tour_id.required' => 'Tour ID is required.',
            'tour_id.exists' => 'The selected tour does not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.date_format' => 'Start date must be in Y-m-d format.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.required' => 'End date is required.',
            'end_date.date_format' => 'End date must be in Y-m-d format.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'from.date_format' => 'From date must be in Y-m-d format.',
            'to.date_format' => 'To date must be in Y-m-d format.',
            'max_people.required' => 'Max people is required.',
            'status.in' => 'Status must be available, full, or cancelled.',
        ];
    }
}
