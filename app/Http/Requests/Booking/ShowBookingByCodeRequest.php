<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ShowBookingByCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'booking_code' => $this->route('booking_code'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_code.regex' => 'The booking code format is invalid. (Mã đặt chỗ không hợp lệ.)',
        ];
    }
}
