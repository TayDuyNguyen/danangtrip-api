<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for listing promotions (admin).
 * (Yêu cầu danh sách khuyến mãi — admin)
 */
class IndexPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('valid_now')) {
            $validNow = $this->query('valid_now');

            if ($validNow === '') {
                $this->merge(['valid_now' => null]);

                return;
            }

            if (is_string($validNow)) {
                $normalized = strtolower(trim($validNow));
                if (in_array($normalized, ['true', '1'], true)) {
                    $this->merge(['valid_now' => true]);
                } elseif (in_array($normalized, ['false', '0'], true)) {
                    $this->merge(['valid_now' => false]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive,expired'],
            'valid_now' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort_by' => ['nullable', 'in:code,name,created_at,starts_at,ends_at,used_count'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
