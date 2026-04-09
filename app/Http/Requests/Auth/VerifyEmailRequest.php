<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'The OTP code is required. (Mã OTP là bắt buộc.)',
        ];
    }
}
