<?php

namespace App\Http\Requests\Category;

use App\Enums\Pagination;
use Illuminate\Foundation\Http\FormRequest;

class IndexCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'per_page must be an integer. (per_page phải là số nguyên.)',
            'per_page.min' => 'per_page must be at least 1. (per_page phải lớn hơn hoặc bằng 1.)',
            'per_page.max' => 'per_page may not be greater than 100. (per_page không được lớn hơn 100.)',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('per_page')) {
            $this->merge(['per_page' => Pagination::PER_PAGE->value]);
        }
    }
}
