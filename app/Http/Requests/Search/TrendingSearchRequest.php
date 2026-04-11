<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TrendingSearchRequest
 * Validates trending query requests.
 * (Xác thực yêu cầu từ khóa xu hướng)
 */
class TrendingSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn phải ít nhất là 1.)',
            'limit.max' => 'The limit must not exceed 50. (Giới hạn không được vượt quá 50.)',
        ];
    }
}
