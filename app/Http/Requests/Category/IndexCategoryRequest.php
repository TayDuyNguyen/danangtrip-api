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
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'all' => ['sometimes', 'nullable', 'boolean'],
            'with_stats' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'q must not exceed 100 characters. (q không được vượt quá 100 ký tự.)',
            'search.max' => 'search must not exceed 100 characters. (search không được vượt quá 100 ký tự.)',
            'status.in' => 'status must be active or inactive. (status phải là active hoặc inactive.)',
            'per_page.integer' => 'per_page must be an integer. (per_page phải là số nguyên.)',
            'per_page.min' => 'per_page must be at least 1. (per_page phải lớn hơn hoặc bằng 1.)',
            'per_page.max' => 'per_page may not be greater than 100. (per_page không được lớn hơn 100.)',
            'all.boolean' => 'all must be true or false. (all phải là true hoặc false.)',
            'with_stats.boolean' => 'with_stats must be true or false. (with_stats phải là true hoặc false.)',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if (! $this->has('per_page') && ! $this->boolean('all')) {
            $payload['per_page'] = Pagination::PER_PAGE->value;
        }

        if ($this->has('q') && ! $this->has('search')) {
            $payload['search'] = $this->query('q');
        }

        if ($this->has('with_stats')) {
            $withStats = $this->query('with_stats');

            if (is_string($withStats)) {
                $normalized = strtolower(trim($withStats));
                if (in_array($normalized, ['true', '1'], true)) {
                    $payload['with_stats'] = true;
                } elseif (in_array($normalized, ['false', '0'], true)) {
                    $payload['with_stats'] = false;
                }
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
}
