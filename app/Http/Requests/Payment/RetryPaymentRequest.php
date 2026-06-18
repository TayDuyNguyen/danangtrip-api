<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetryPaymentRequest extends FormRequest
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
            'payment_method' => ['nullable', 'string', Rule::in(PaymentMethod::customerCheckoutMethods())],
            'return_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_code.regex' => 'The booking code format is invalid. (Mã đặt chỗ không hợp lệ.)',
            'payment_method.in' => 'The selected payment method is invalid.',
            'return_url.url' => 'The return URL format is invalid.',
        ];
    }
}
