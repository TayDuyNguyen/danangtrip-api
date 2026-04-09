<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class ShowCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The category ID is required. (Mã danh mục là bắt buộc.)',
            'id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
        ];
    }
}
