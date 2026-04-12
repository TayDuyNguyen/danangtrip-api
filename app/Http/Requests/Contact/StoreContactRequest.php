<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreContactRequest
 * Validates request for submitting a contact form.
 * (Xác thực yêu cầu gửi form liên hệ)
 */
class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\s\-\.]{10,15}$/',
            ],
            'subject' => [
                'nullable',
                'string',
                'max:200',
            ],
            'message' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name is required. (Họ tên là bắt buộc.)',
            'name.max' => 'The name must not exceed 100 characters. (Họ tên không được vượt quá 100 ký tự.)',
            'email.required' => 'The email address is required. (Địa chỉ email là bắt buộc.)',
            'email.email' => 'Please provide a valid email address. (Vui lòng cung cấp địa chỉ email hợp lệ.)',
            'email.max' => 'The email must not exceed 100 characters. (Email không được vượt quá 100 ký tự.)',
            'phone.max' => 'The phone number must not exceed 20 characters. (Số điện thoại không được vượt quá 20 ký tự.)',
            'phone.regex' => 'The phone number format is invalid. (Định dạng số điện thoại không hợp lệ.)',
            'subject.max' => 'The subject must not exceed 200 characters. (Tiêu đề không được vượt quá 200 ký tự.)',
            'message.required' => 'The message is required. (Nội dung tin nhắn là bắt buộc.)',
        ];
    }
}
