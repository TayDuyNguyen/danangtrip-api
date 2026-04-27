<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class ShowBlogRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:blog_posts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Blog post ID is required. (ID bài viết là bắt buộc.)',
            'id.integer' => 'Blog post ID must be an integer. (ID bài viết phải là số nguyên.)',
            'id.exists' => 'Blog post not found. (Bài viết không tồn tại.)',
        ];
    }
}
