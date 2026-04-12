<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class RatingsProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'in:pending,approved,rejected',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid.',
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page must not exceed 100.',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
        ];
    }
}
