<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'username' => [
                'sometimes',
                'string',
                'max:50',
                'unique:users,username,1,id',
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:100',
                'unique:users,email,1,id',
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
            ],
            'full_name' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\\+?[0-9\\s\\-\\.]{10,15}\$/',
            ],
            'birthdate' => [
                'nullable',
                'date',
            ],
            'gender' => [
                'nullable',
                'string',
                'max:20',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'role' => [
                'sometimes',
                'string',
                'in:admin,partner,user',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The user ID is required. (Mã người dùng là bắt buộc.)',
            'id.integer' => 'The user ID must be an integer. (Mã người dùng phải là số nguyên.)',
            'id.exists' => 'The user ID does not exist. (Mã người dùng không tồn tại.)',
            'username.required' => 'The username is required. (Tên người dùng là bắt buộc.)',
            'username.unique' => 'This username is already taken. (Tên người dùng này đã được sử dụng.)',
            'username.max' => 'The username must not exceed 50 characters. (Tên người dùng không được vượt quá 50 ký tự.)',
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.unique' => 'This email address is already taken. (Địa chỉ email này đã được sử dụng.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'password.required' => 'The password field is required. (Trường mật khẩu là bắt buộc.)',
            'password.min' => 'The password must be at least 8 characters. (Mật khẩu phải có ít nhất 8 ký tự.)',
            'full_name.required' => 'The full name is required. (Họ và tên là bắt buộc.)',
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'phone.regex' => 'The phone number format is invalid. (Định dạng số điện thoại không hợp lệ.)',
            'birthdate.date_format' => 'Please provide birthdate in YYYY-MM-DD format. (Vui lòng cung cấp ngày sinh theo định dạng YYYY-MM-DD.)',
            'gender.max' => 'The gender must not exceed 20 characters. (Giới tính không được vượt quá 20 ký tự.)',
            'city.max' => 'The city name must not exceed 100 characters. (Tên thành phố không được vượt quá 100 ký tự.)',
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
        ];
    }
}
