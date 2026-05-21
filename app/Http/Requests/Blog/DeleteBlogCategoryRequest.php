<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class DeleteBlogCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:blog_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Blog category ID is required. (ID danh mục blog là bắt buộc.)',
            'id.integer' => 'Blog category ID must be an integer. (ID danh mục blog phải là số nguyên.)',
            'id.exists' => 'Blog category not found. (Danh mục blog không tồn tại.)',
        ];
    }
}
