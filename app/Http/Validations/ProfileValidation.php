<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class ProfileValidation
 * Provides centralized validation logic for user profile management.
 * (Cung cấp logic xác thực tập trung cho quản lý thông tin cá nhân)
 */
final class ProfileValidation
{
    /**
     * Validate update profile request.
     * (Xác thực yêu cầu cập nhật thông tin cá nhân)
     */
    public static function validateUpdate(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'full_name' => 'sometimes|string|max:100',
                'phone' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^\+?[0-9\s\-\.]{10,15}$/',
                ],
                'birthdate' => 'sometimes|nullable|date_format:Y-m-d',
                'gender' => 'sometimes|nullable|in:male,female,other',
                'city' => 'sometimes|nullable|string|max:50',
            ],
            self::messages()
        );
    }

    /**
     * Validate upload avatar request.
     * (Xác thực yêu cầu upload ảnh đại diện)
     */
    public static function validateAvatar(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ],
            self::messages()
        );
    }

    /**
     * Validate change password request.
     * (Xác thực yêu cầu đổi mật khẩu)
     */
    public static function validatePassword(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ],
            self::messages()
        );
    }

    /**
     * Validate rating history request.
     * (Xác thực yêu cầu xem lịch sử đánh giá)
     */
    public static function validateRatings(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'status' => 'sometimes|in:pending,approved,rejected',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
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
            'full_name.string' => 'The full name must be a string.',
            'full_name.max' => 'The full name must not exceed 100 characters.',
            'phone.max' => 'The phone number must not exceed 20 characters.',
            'phone.regex' => 'The phone number format is invalid.',
            'birthdate.date' => 'The birthdate must be a valid date.',
            'gender.in' => 'The selected gender is invalid.',
            'city.max' => 'The city name must not exceed 50 characters.',
            'avatar.required' => 'The avatar file is required.',
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'The avatar must be a file of type: jpeg, png, jpg.',
            'avatar.max' => 'The avatar size must not exceed 2MB.',
            'current_password.required' => 'The current password is required.',
            'password.required' => 'The new password is required.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'status.in' => 'The selected status is invalid.',
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page must not exceed 100.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
        ];
    }
}
