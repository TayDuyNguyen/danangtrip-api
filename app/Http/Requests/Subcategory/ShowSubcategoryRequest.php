<?php

namespace App\Http\Requests\Subcategory;

use Illuminate\Foundation\Http\FormRequest;

class ShowSubcategoryRequest extends FormRequest
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
                'exists:subcategories,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The subcategory ID is required. (Mã danh mục con là bắt buộc.)',
            'id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
        ];
    }
}
