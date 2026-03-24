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
     * Validate list users request.
     * (Xác thực yêu cầu danh sách người dùng)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'q' => 'sometimes|string|max:100',
                'role' => 'sometimes|string|in:admin,partner,user',
                'status' => 'sometimes|string|in:active,banned',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate show user request.
     * (Xác thực yêu cầu chi tiết người dùng)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            ['id' => 'required|integer|exists:users,id'],
            self::messages()
        );
    }

    /**
     * Validate delete user request.
     * (Xác thực yêu cầu xóa người dùng)
     */
    public static function validateDelete(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:users,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate update status request.
     * (Xác thực yêu cầu cập nhật trạng thái người dùng)
     */
    public static function validateUpdateStatus(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:users,id',
                'status' => 'required|string|in:active,banned',
            ],
            self::messages()
        );
    }

    /**
     * Validate update role request.
     * (Xác thực yêu cầu cập nhật vai trò người dùng)
     */
    public static function validateUpdateRole(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:users,id',
                'role' => 'required|string|in:admin,partner,user',
            ],
            self::messages()
        );
    }

    /**
     * Validate store user request.
     * (Xác thực yêu cầu tạo người dùng mới)
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
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^\+?[0-9\s\-\.]{10,15}$/',
                ],
                'birthdate' => 'nullable|date_format:Y-m-d',
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
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:users,id',
                'username' => 'sometimes|string|max:50|unique:users,username,'.$id.',id',
                'email' => 'sometimes|string|email|max:100|unique:users,email,'.$id.',id',
                'password' => 'sometimes|string|min:8',
                'full_name' => 'sometimes|string|max:100',
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^\+?[0-9\s\-\.]{10,15}$/',
                ],
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
     */
    protected static function messages(): array
    {
        return [
            'id.required' => 'The user ID is required. (Mã người dùng là bắt buộc.)',
            'id.integer' => 'The user ID must be an integer. (Mã người dùng phải là số nguyên.)',
            'id.exists' => 'The user ID does not exist. (Mã người dùng không tồn tại.)',
            'username.required' => 'The username is required. (Tên người dùng là bắt buộc.)',
            'username.unique' => 'This username is already taken. (Tên người dùng này đã được sử dụng.)',
            'username.max' => 'The username must not exceed 50 characters. (Tên người dùng không được vượt quá 50 ký tự.)',
            'full_name.required' => 'The full name is required. (Họ và tên là bắt buộc.)',
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.unique' => 'This email address is already taken. (Địa chỉ email này đã được sử dụng.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'password.required' => 'The password field is required. (Trường mật khẩu là bắt buộc.)',
            'password.min' => 'The password must be at least 8 characters. (Mật khẩu phải có ít nhất 8 ký tự.)',
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'phone.regex' => 'The phone number format is invalid. (Định dạng số điện thoại không hợp lệ.)',
            'birthdate.date_format' => 'Please provide birthdate in YYYY-MM-DD format. (Vui lòng cung cấp ngày sinh theo định dạng YYYY-MM-DD.)',
            'gender.max' => 'The gender must not exceed 20 characters. (Giới tính không được vượt quá 20 ký tự.)',
            'city.max' => 'The city name must not exceed 100 characters. (Tên thành phố không được vượt quá 100 ký tự.)',
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
        ];
    }
}
