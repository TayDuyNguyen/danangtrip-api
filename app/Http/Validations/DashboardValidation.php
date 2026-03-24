<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class DashboardValidation
 * Provides validation logic for admin dashboard and reports.
 * (Cung cấp logic xác thực cho dashboard và báo cáo của admin)
 */
final class DashboardValidation
{
    /**
     * Validate location reports request.
     * (Xác thực yêu cầu báo cáo địa điểm)
     */
    public static function validateLocationReports(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'from' => 'sometimes|date_format:Y-m-d',
                'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
            ],
            self::messages()
        );
    }

    /**
     * Validate rating reports request.
     * (Xác thực yêu cầu báo cáo đánh giá)
     */
    public static function validateRatingReports(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'from' => 'sometimes|date_format:Y-m-d',
                'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
                'status' => 'sometimes|string|in:pending,approved,rejected',
            ],
            self::messages()
        );
    }

    /**
     * Validate user reports request.
     * (Xác thực yêu cầu báo cáo người dùng)
     */
    public static function validateUserReports(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'year' => 'sometimes|integer|min:2000|max:'.(date('Y') + 1),
            ],
            self::messages()
        );
    }

    /**
     * Validate point reports request.
     * (Xác thực yêu cầu báo cáo giao dịch điểm)
     */
    public static function validatePointReports(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'from' => 'sometimes|date_format:Y-m-d',
                'to' => 'sometimes|date_format:Y-m-d|after_or_equal:from',
                'type' => 'sometimes|string|in:purchase,spend,bonus,refund',
            ],
            self::messages()
        );
    }

    /**
     * Centralized validation error messages.
     * (Thông báo lỗi xác thực tập trung)
     */
    protected static function messages(): array
    {
        return [
            'from.date_format' => 'The from date must be in YYYY-MM-DD format.',
            'to.date_format' => 'The to date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The to date must be a date after or equal to from date.',
            'status.in' => 'The selected status is invalid.',
            'year.integer' => 'The year must be an integer.',
            'year.min' => 'The year must be at least 2000.',
            'year.max' => 'The year cannot be in the far future.',
            'type.in' => 'The selected transaction type is invalid.',
        ];
    }
}
