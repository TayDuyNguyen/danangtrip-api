<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'The cancellation reason is required.',
            'cancellation_reason.string' => 'The cancellation reason must be a string.',
            'cancellation_reason.max' => 'The cancellation reason may not exceed :max characters.',
        ];
    }
}
