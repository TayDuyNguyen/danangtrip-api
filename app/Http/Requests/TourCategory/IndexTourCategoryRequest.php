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

    protected function prepareForValidation(): void
    {
        $withStats = $this->query('with_stats');

        if ($withStats === '') {
            $this->merge(['with_stats' => null]);

            return;
        }

        if (is_string($withStats)) {
            $normalized = strtolower(trim($withStats));

            if (in_array($normalized, ['true', '1'], true)) {
                $this->merge(['with_stats' => true]);
            } elseif (in_array($normalized, ['false', '0'], true)) {
                $this->merge(['with_stats' => false]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
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
            'with_stats' => [
                'sometimes',
                'nullable',
                'in:true,false,1,0',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'The search must not exceed 100 characters. (Từ khóa tìm kiếm không được vượt quá 100 ký tự.)',
            'status.in' => 'The status must be active or inactive. (Trạng thái phải là active hoặc inactive.)',
            'per_page.integer' => 'The per_page must be an integer. (per_page phải là số nguyên.)',
            'per_page.min' => 'The per_page must be at least 1. (per_page phải ít nhất là 1.)',
            'per_page.max' => 'The per_page must not exceed 100. (per_page không được vượt quá 100.)',
            'with_stats.in' => 'The with_stats must be true/false or 1/0. (with_stats phải là true/false hoặc 1/0.)',
        ];
    }
}
