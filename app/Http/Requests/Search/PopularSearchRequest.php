<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PopularSearchRequest
 * Validates popular query requests.
 * (Xác thực yêu cầu từ khóa phổ biến)
 */
class PopularSearchRequest extends FormRequest
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
            'days' => [
                'sometimes',
                'integer',
                'min:1',
                'max:365',
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
            'days.integer' => 'The days must be an integer. (Số ngày phải là số nguyên.)',
        ];
    }
}
