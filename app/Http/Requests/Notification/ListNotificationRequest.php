<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class ListNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_read' => [
                'sometimes',
                'boolean',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
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
            'is_read.boolean' => 'The is_read filter must be a boolean (true or false).',
            'page.integer' => 'The page number must be an integer.',
            'page.min' => 'The page number must be at least 1.',
            'per_page.integer' => 'The items per page must be an integer.',
            'per_page.min' => 'The items per page must be at least 1.',
            'per_page.max' => 'The items per page may not be greater than 100.',
        ];
    }
}
