<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TopToursDashboardRequest
 * Validates request for top tours statistics.
 * (Xác thực yêu cầu thống kê top tour bán chạy)
 */
class TopToursDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],
            'from' => [
                'sometimes',
                'date_format:Y-m-d',
            ],
            'to' => [
                'sometimes',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit must not exceed 50.',
            'from.date_format' => 'The from date must be in YYYY-MM-DD format.',
            'to.date_format' => 'The to date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The to date must be after or equal to the from date.',
        ];
    }
}
