<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class NotificationValidation
 * Provides validation logic for notification-related operations.
 * (Cung cấp logic xác thực cho các hoạt động liên quan đến thông báo)
 */
final class NotificationValidation
{
    /**
     * Validate the request for listing notifications.
     * (Xác thực yêu cầu lấy danh sách thông báo)
     */
    public static function validateList(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'is_read' => 'sometimes|boolean',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
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
            'is_read.boolean' => 'The is_read filter must be a boolean (true or false).',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page may not be greater than 100.',
        ];
    }
}
