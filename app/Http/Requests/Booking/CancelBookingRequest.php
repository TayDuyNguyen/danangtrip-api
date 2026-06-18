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
            'refund_bank_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'refund_account_no' => ['nullable', 'string', 'min:6', 'max:30', 'regex:/^[0-9]+$/'],
            'refund_account_name' => ['nullable', 'string', 'min:2', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'The cancellation reason is required.',
            'cancellation_reason.string' => 'The cancellation reason must be a string.',
            'cancellation_reason.max' => 'The cancellation reason may not exceed :max characters.',
            'refund_account_no.regex' => 'The refund account number may contain digits only.',
        ];
    }
}
