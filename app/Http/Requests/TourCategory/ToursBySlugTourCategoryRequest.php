<?php

namespace App\Http\Requests\TourCategory;

use Illuminate\Foundation\Http\FormRequest;

class ToursBySlugTourCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'exists:tour_categories,slug',
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
            'sort_by' => [
                'sometimes',
                'string',
                'in:created_at,price,rating_avg',
            ],
            'sort_order' => [
                'sometimes',
                'string',
                'in:asc,desc',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Slug is required.',
            'slug.exists' => 'The selected slug does not exist.',
        ];
    }
}
