<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'content' => [
                'sometimes',
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
                'sometimes',
                'array',
            ],
            'category_ids.*' => [
                'sometimes',
                'integer',
                'exists:blog_categories,id',
            ],
            'status' => [
                'sometimes',
                'in:draft,published,archived',
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
            'title.max' => 'The title may not be greater than 255 characters.',
            'category_ids.required' => 'At least one category is required. (Cần ít nhất một danh mục.)',
            'category_ids.*.exists' => 'One or more selected categories are invalid. (Một hoặc nhiều danh mục không hợp lệ.)',
            'status.in' => 'The selected status is invalid.',
            'published_at.date_format' => 'The published date format must be Y-m-d H:i:s.',
        ];
    }
}
