<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;

class FiltersPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_status' => [
                'nullable',
                'string',
                'in:'.implode(',', PaymentStatus::values()),
            ],
            'payment_gateway' => [
                'nullable',
                'string',
                'max:50',
            ],
            'refund_status' => [
                'nullable',
                'string',
                'in:pending,processing,completed,failed,rejected',
            ],
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.in' => 'The selected payment status is invalid.',
            'date_from.date_format' => 'From date must be in Y-m-d format.',
            'date_to.date_format' => 'To date must be in Y-m-d format.',
            'date_to.after_or_equal' => 'To date must be after or equal to from date.',
            'per_page.max' => 'Items per page may not be greater than 100.',
        ];
    }
}
