<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    PaymentMethod::BANK_TRANSFER->value,
                    PaymentMethod::SEPAY->value,
                    PaymentMethod::PAYOS->value,
                    PaymentMethod::MOMO->value,
                    PaymentMethod::VNPAY->value,
                    PaymentMethod::ZALOPAY->value,
                ]),
            ],
            'return_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID is required.',
            'booking_id.integer' => 'Booking ID must be an integer.',
            'booking_id.exists' => 'The selected booking does not exist.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'The selected payment method is invalid.',
            'return_url.url' => 'The return URL format is invalid.',
        ];
    }
}
