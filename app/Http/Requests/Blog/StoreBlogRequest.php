<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:200',
            ],
            'content' => [
                'required',
                'string',
            ],
            'excerpt' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'featured_image' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'category_ids' => [
                'required',
                'array',
            ],
            'status' => [
                'sometimes',
                'in:draft,published',
            ],
            'published_at' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The blog title is required.',
            'content.required' => 'The blog content is required.',
            'category_ids.required' => 'At least one category is required.',
            'category_ids.*.exists' => 'One or more selected categories are invalid.',
            'status.in' => 'The selected status is invalid.',
            'published_at.date' => 'The published date is not a valid date.',
        ];
    }
}
