<?php

namespace App\Http\Requests\Category;

use App\Enums\Pagination;
use Illuminate\Foundation\Http\FormRequest;

class LocationsBySlugCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
            'per_page' => $this->input('per_page', Pagination::PER_PAGE->value),
        ]);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'regex:/^[a-z0-9-]+$/'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Category slug is required. (Slug danh mục là bắt buộc.)',
            'slug.regex' => 'Category slug format is invalid. (Định dạng slug danh mục không hợp lệ.)',
            'per_page.integer' => 'per_page must be an integer. (per_page phải là số nguyên.)',
            'per_page.min' => 'per_page must be at least 1. (per_page phải lớn hơn hoặc bằng 1.)',
            'per_page.max' => 'per_page may not be greater than 100. (per_page không được lớn hơn 100.)',
        ];
    }
}
