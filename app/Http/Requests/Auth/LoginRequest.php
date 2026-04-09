<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'password.required' => 'The password is required. (Mật khẩu là bắt buộc.)',
            'password.min' => 'The password must be at least 8 characters. (Mật khẩu phải có ít nhất 8 ký tự.)',
        ];
    }
}
