<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
            ],
            'items.*.sort_order' => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Items are required.',
            'items.array' => 'Items must be an array.',
            'items.min' => 'At least one item is required.',
            'items.*.id.required' => 'Category ID is required.',
            'items.*.id.integer' => 'Category ID must be an integer.',
            'items.*.id.distinct' => 'Category IDs must not be duplicated.',
            'items.*.sort_order.required' => 'Sort order is required.',
            'items.*.sort_order.integer' => 'Sort order must be an integer.',
            'items.*.sort_order.min' => 'Sort order must be at least 1.',
            'items.*.sort_order.distinct' => 'Sort order values must not be duplicated.',
        ];
    }
}
