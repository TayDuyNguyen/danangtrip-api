<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class RatingReportsDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => [
                'sometimes',
                'date_format:Y-m-d',
            ],
            'to' => [
                'sometimes',
                'date_format:Y-m-d',
                'after_or_equal:from',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:pending,approved,rejected',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'The from date must be in YYYY-MM-DD format.',
            'to.date_format' => 'The to date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The to date must be a date after or equal to from date.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
