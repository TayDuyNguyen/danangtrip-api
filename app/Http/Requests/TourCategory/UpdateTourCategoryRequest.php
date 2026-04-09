<?php

namespace App\Http\Requests\TourCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:tour_categories,id',
            ],
            'name' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                'unique:tour_categories,slug,1,id',
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
            'id.required' => 'Tour category ID is required.',
            'id.exists' => 'The selected tour category does not exist.',
            'name.required' => 'Tour category name is required.',
            'slug.required' => 'Slug is required.',
            'slug.exists' => 'The selected slug does not exist.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
