<?php

namespace App\Http\Requests\Amenity;

use Illuminate\Foundation\Http\FormRequest;

class IndexAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => [
                'sometimes',
                'string',
                'in:connectivity,parking,comfort,payment',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'The amenity category is required.',
            'category.in' => 'The selected amenity category is invalid.',
        ];
    }
}
