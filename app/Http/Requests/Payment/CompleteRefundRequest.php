<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_reference' => ['required', 'string', 'min:4', 'max:150'],
            'approved_amount' => ['nullable', 'numeric', 'gt:0'],
            'evidence_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
