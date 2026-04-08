<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
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
            'role' => 'sometimes|in:admin,partner,user',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'The username is required. (Tên người dùng là bắt buộc.)',
            'username.unique' => 'This username is already taken. (Tên người dùng này đã được sử dụng.)',
            'username.max' => 'The username must not exceed 50 characters. (Tên người dùng không được vượt quá 50 ký tự.)',
            'full_name.required' => 'The full name is required. (Họ và tên là bắt buộc.)',
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.unique' => 'This email address is already registered. (Địa chỉ email này đã được đăng ký.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'password.required' => 'The password is required. (Mật khẩu là bắt buộc.)',
            'password.min' => 'The password must be at least 8 characters. (Mật khẩu phải có ít nhất 8 ký tự.)',
            'password.confirmed' => 'The password confirmation does not match. (Xác nhận mật khẩu không khớp.)',
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'phone.regex' => 'The phone number format is invalid. (Định dạng số điện thoại không hợp lệ.)',
            'birthdate.date' => 'Please provide a valid birthdate. (Vui lòng cung cấp ngày sinh hợp lệ.)',
            'gender.max' => 'The gender must not exceed 20 characters. (Giới tính không được vượt quá 20 ký tự.)',
            'city.max' => 'The city name must not exceed 100 characters. (Tên thành phố không được vượt quá 100 ký tự.)',
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
        ];
    }
}
