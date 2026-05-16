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
        $payload = [];
        $withStats = $this->query('with_stats');

        if ($withStats === '') {
            $this->merge(['with_stats' => null]);

            return;
        }

        if (is_string($withStats)) {
            $normalized = strtolower(trim($withStats));

            if (in_array($normalized, ['true', '1'], true)) {
                $payload['with_stats'] = true;
            } elseif (in_array($normalized, ['false', '0'], true)) {
                $payload['with_stats'] = false;
            }
        }

        if ($this->has('all')) {
            $all = $this->query('all');

            if (is_string($all)) {
                $normalized = strtolower(trim($all));
                if (in_array($normalized, ['true', '1'], true)) {
                    $payload['all'] = true;
                } elseif (in_array($normalized, ['false', '0'], true)) {
                    $payload['all'] = false;
                }
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
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
            'all' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
            'with_stats' => [
                'sometimes',
                'nullable',
                'boolean',
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
            'all.boolean' => 'The all must be true or false. (all phải là true hoặc false.)',
            'with_stats.boolean' => 'The with_stats must be true or false. (with_stats phải là true hoặc false.)',
        ];
    }
}
