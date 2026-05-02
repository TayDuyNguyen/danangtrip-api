<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ShowBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:bookings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Booking ID is required. (ID đơn đặt là bắt buộc.)',
            'id.integer' => 'Booking ID must be an integer. (ID đơn đặt phải là số nguyên.)',
            'id.exists' => 'Booking not found. (Đơn đặt không tồn tại.)',
        ];
    }
}
