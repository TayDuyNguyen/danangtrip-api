<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class PointValidation
 * Provides centralized validation logic for point-related operations.
 * (Cung cấp logic xác thực tập trung cho các hoạt động liên quan đến điểm)
 */
final class PointValidation
{
    /**
     * Validate point transactions request.
     * (Xác thực yêu cầu danh sách giao dịch điểm)
     */
    public static function validateTransactions(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'type' => 'sometimes|in:purchase,spend,bonus,refund',
                'status' => 'sometimes|in:pending,completed,failed',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
            ],
            self::messages()
        );
    }

    /**
     * Validate point purchase request.
     * (Xác thực yêu cầu nạp điểm)
     */
    public static function validatePurchase(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'amount' => 'required|integer|min:1000',
                'payment_method' => 'required|in:momo,vnpay,bank',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    protected static function messages(): array
    {
        return [
            'type.in' => 'The selected transaction type is invalid.',
            'status.in' => 'The selected status is invalid.',
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page must not exceed 100.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
            'amount.required' => 'The purchase amount is required.',
            'amount.integer' => 'The purchase amount must be an integer.',
            'amount.min' => 'The minimum purchase amount is 1000.',
            'payment_method.required' => 'The payment method is required.',
            'payment_method.in' => 'The selected payment method is invalid.',
        ];
    }
}
