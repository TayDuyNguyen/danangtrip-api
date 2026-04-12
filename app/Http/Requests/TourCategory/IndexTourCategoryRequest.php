<?php

namespace App\Http\Requests\TourCategory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexTourCategoryRequest
 * Validates query parameters for listing tour categories (Admin).
 * (Xác thực tham số truy vấn cho danh sách danh mục tour - Admin)
 */
class IndexTourCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:active,inactive',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The status must be active or inactive. (Trạng thái phải là active hoặc inactive.)',
            'per_page.integer' => 'The per_page must be an integer. (per_page phải là số nguyên.)',
            'per_page.min' => 'The per_page must be at least 1. (per_page phải ít nhất là 1.)',
            'per_page.max' => 'The per_page must not exceed 100. (per_page không được vượt quá 100.)',
        ];
    }
}
