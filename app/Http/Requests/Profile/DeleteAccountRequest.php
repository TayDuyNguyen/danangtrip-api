<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'The password is required. (Mật khẩu là bắt buộc.)',
        ];
    }
}
