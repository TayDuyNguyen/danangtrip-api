<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ShowPaymentRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:payments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Payment ID is required. (ID thanh toán là bắt buộc.)',
            'id.integer' => 'Payment ID must be an integer. (ID thanh toán phải là số nguyên.)',
            'id.exists' => 'Payment not found. (Thanh toán không tồn tại.)',
        ];
    }
}
