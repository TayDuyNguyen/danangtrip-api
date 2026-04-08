<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class PopularWithFiltersSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'days' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'filters' => [
                'sometimes',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.max' => 'The limit is too large. (Giới hạn quá lớn.)',
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'days.max' => 'The days value is too large. (Giá trị days quá lớn.)',
            'days.integer' => 'The days must be an integer.',
            'days.min' => 'The days must be at least 1.',
        ];
    }
}
