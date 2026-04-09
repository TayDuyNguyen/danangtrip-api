<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

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
                'in:bank_transfer,credit_card,paypal,cash,momo,vnpay,zalopay',
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
        ];
    }
}
