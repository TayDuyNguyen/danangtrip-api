<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for updating an existing promotion.
 * (Yêu cầu cập nhật khuyến mãi)
 */
class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['sometimes', 'in:percent,fixed'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['nullable', 'in:active,inactive,expired'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Code may only contain letters, numbers, underscores and hyphens.',
            'discount_type.in' => 'Discount type must be percent or fixed.',
            'ends_at.after_or_equal' => 'End date must be on or after start date.',
        ];
    }
}
