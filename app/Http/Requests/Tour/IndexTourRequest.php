<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class IndexTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_category_id' => [
                'sometimes',
                'integer',
                'exists:tour_categories,id',
            ],
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                'in:available,unavailable,pending,active,inactive',
            ],
            'is_featured' => [
                'sometimes',
                'boolean',
            ],
            'is_hot' => [
                'sometimes',
                'boolean',
            ],
            'order_by' => [
                'sometimes',
                'in:created_at,price_adult,rating_avg',
            ],
            'order_dir' => [
                'sometimes',
                'in:asc,desc',
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
            'tour_category_id.required' => 'Category is required.',
            'tour_category_id.integer' => 'Category ID must be an integer.',
            'tour_category_id.exists' => 'The selected category does not exist.',
            'status.in' => 'Invalid status value.',
            'is_featured.boolean' => 'Featured flag must be a boolean.',
            'is_hot.boolean' => 'Hot flag must be a boolean.',
            'per_page.max' => 'Items per page may not be greater than 100.',
        ];
    }
}
