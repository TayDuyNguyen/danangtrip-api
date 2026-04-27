<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ShowPaymentStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'transaction_code' => $this->route('transaction_code'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,100}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_code.regex' => 'The transaction code format is invalid. (Mã giao dịch không hợp lệ.)',
        ];
    }
}
