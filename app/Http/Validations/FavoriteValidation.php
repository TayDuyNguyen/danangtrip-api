<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class FavoriteValidation.
 * (Lớp xác thực cho Yêu thích)
 */
final class FavoriteValidation
{
    /**
     * Validate the request to store a favorite.
     * (Xác thực yêu cầu lưu yêu thích)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'location_id' => 'required|integer|exists:locations,id',
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
            'location_id.required' => 'The location ID is required.',
            'location_id.integer' => 'The location ID must be an integer.',
            'location_id.exists' => 'The location does not exist.',
        ];
    }
}
