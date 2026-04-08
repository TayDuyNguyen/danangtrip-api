<?php

namespace App\Http\Requests\Favorite;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required' => 'The location ID is required.',
            'location_id.integer' => 'The location ID must be an integer.',
            'location_id.exists' => 'The location does not exist.',
        ];
    }
}
