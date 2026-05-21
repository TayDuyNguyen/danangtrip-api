<?php

namespace App\Http\Requests\Location;

use App\Enums\LocationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('q') && ! $this->has('search')) {
            $merged['search'] = $this->query('q');
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'q' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],
            'district' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'price_level' => [
                'sometimes',
                'integer',
                Rule::in([1, 2, 3, 4]),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(LocationStatus::values()),
            ],
            'sort_by' => [
                'sometimes',
                'in:created_at,avg_rating,review_count,view_count,price_min',
            ],
            'sort_order' => [
                'sometimes',
                'in:asc,desc',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
