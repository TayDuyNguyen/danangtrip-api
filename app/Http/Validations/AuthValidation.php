<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class AuthValidation
 * Provides centralized validation logic using direct English error messages.
 * (Cung cấp logic xác thực tập trung sử dụng thông báo lỗi tiếng Anh trực tiếp)
 */
class AuthValidation
{
    /**
     * Validate login request.
     * (Xác thực yêu cầu đăng nhập)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateLogin(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8'
            ],
            [
                'email.required' => 'The email address is required.',
                'email.email' => 'Please provide a valid email address.',
                'email.max' => 'The email address must not exceed 255 characters.',
                'password.required' => 'The password is required.',
                'password.min' => 'The password must be at least 8 characters.',
            ]
        );
    }

    /**
     * Validate registration/create user request.
     * (Xác thực yêu cầu đăng ký/tạo người dùng)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateRegister(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'username' => 'required|string|max:50|unique:users,username',
                'email' => 'required|email|max:100|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'full_name' => 'required|string|max:100',
                'phone' => 'nullable|string|max:20',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'role' => 'sometimes|in:admin,partner,user',
            ],
            [
                'username.required' => 'The username is required.',
                'username.unique' => 'This username is already taken.',
                'username.max' => 'The username must not exceed 50 characters.',
                'full_name.required' => 'The full name is required.',
                'email.required' => 'The email address is required.',
                'email.unique' => 'This email address is already registered.',
                'email.max' => 'The email must not exceed 100 characters.',
                'password.required' => 'The password is required.',
                'password.min' => 'The password must be at least 8 characters.',
                'password.confirmed' => 'The password confirmation does not match.',
            ]
        );
    }

    /**
     * Validate reset password request.
     * (Xác thực yêu cầu đặt lại mật khẩu)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateResetPassword(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'email' => 'required|email|exists:users,email',
            ],
            [
                'email.required' => 'The email address is required.',
                'email.exists' => 'We could not find a user with that email address.',
            ]
        );
    }
}
