<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for public promotion code validation.
 * (Yêu cầu kiểm tra mã giảm giá công khai)
 */
class ValidatePromotionCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Promotion code is required.',
            'order_total.required' => 'Order total is required for discount calculation.',
            'order_total.numeric' => 'Order total must be a valid number.',
        ];
    }
}
