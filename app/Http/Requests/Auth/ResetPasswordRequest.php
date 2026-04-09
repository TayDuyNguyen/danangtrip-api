<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.exists' => 'We could not find a user with that email address. (Chúng tôi không tìm thấy người dùng với địa chỉ email đó.)',
            'password.required' => 'The password is required. (Mật khẩu là bắt buộc.)',
            'password.min' => 'The password must be at least 8 characters. (Mật khẩu phải có ít nhất 8 ký tự.)',
            'password.confirmed' => 'The password confirmation does not match. (Xác nhận mật khẩu không khớp.)',
            'token.required' => 'The token is required. (Token là bắt buộc.)',
        ];
    }
}
