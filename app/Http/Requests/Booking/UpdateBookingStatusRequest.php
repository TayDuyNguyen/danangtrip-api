<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_status' => ['required', 'string', Rule::in(BookingStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_status.required' => 'The booking status is required.',
            'booking_status.string' => 'The booking status must be a string.',
            'booking_status.in' => 'The selected booking status is invalid.',
        ];
    }
}
