<?php

namespace App\Http\Requests\TourCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreTourCategoryRequest extends FormRequest
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
                'max:100',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                'unique:tour_categories,slug',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'icon' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'sort_order' => [
                'sometimes',
                'nullable',
                'integer',
            ],
            'status' => [
                'sometimes',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tour category name is required.',
            'slug.required' => 'Slug is required.',
            'slug.exists' => 'The selected slug does not exist.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
