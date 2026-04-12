<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class FeaturedLocationRequest extends FormRequest
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
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn tối thiểu là 1.)',
            'limit.max' => 'The limit must not exceed 100. (Giới hạn tối đa là 100.)',
        ];
    }
}
