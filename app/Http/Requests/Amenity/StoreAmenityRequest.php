<?php

namespace App\Http\Requests\Amenity;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:amenities,name',
            ],
            'icon' => [
                'required',
                'string',
                'max:50',
            ],
            'category' => [
                'required',
                'string',
                'max:30',
                'in:connectivity,parking,comfort,payment',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The amenity name is required.',
            'name.unique' => 'This amenity name already exists.',
            'category.required' => 'The amenity category is required.',
            'category.in' => 'The selected amenity category is invalid.',
        ];
    }
}
