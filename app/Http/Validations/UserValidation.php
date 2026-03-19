<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class UserValidation
 * Provides centralized validation logic for user management using direct English error messages.
 * (Cung cấp logic xác thực tập trung cho quản lý người dùng sử dụng thông báo lỗi tiếng Anh trực tiếp)
 */
final class UserValidation
{
    /**
     * Validate store user request.
     * (Xác thực yêu cầu tạo người dùng mới)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'username' => 'required|string|max:50|unique:users,username',
                'email' => 'required|string|email|max:100|unique:users,email',
                'password' => 'required|string|min:8',
                'full_name' => 'required|string|max:100',
                'phone' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'role' => 'sometimes|string|in:admin,partner,user',
            ],
            self::messages()
        );
    }

    /**
     * Validate update user request.
     * (Xác thực yêu cầu cập nhật người dùng)
     *
     * @param Request $request
     * @param int $id
     * @return ValidatorInstance
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'username' => 'sometimes|string|max:50|unique:users,username,' . $id,
                'email' => 'sometimes|string|email|max:100|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'full_name' => 'sometimes|string|max:100',
                'phone' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'role' => 'sometimes|string|in:admin,partner,user',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     *
     * @return array
     */
    private static function messages(): array
    {
        return [
            'username.required' => 'The username is required.',
            'username.unique' => 'This username is already taken.',
            'username.max' => 'The username must not exceed 50 characters.',
            'full_name.required' => 'The full name is required.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already taken.',
            'email.max' => 'The email must not exceed 100 characters.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.in' => 'The selected role is invalid.',
        ];
    }
}
