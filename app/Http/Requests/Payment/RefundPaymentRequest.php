<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_reason' => [
                'required',
                'string',
                'max:1000',
            ],
            'refund_bank_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'refund_account_no' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9]+$/'],
            'refund_account_name' => ['required', 'string', 'min:2', 'max:120'],
            'transfer_reference' => ['required', 'string', 'min:4', 'max:150'],
            'approved_amount' => ['nullable', 'numeric', 'gt:0'],
            'evidence_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_reason.required' => 'Refund reason is required.',
            'refund_reason.max' => 'Refund reason may not be greater than 1000 characters.',
        ];
    }
}
