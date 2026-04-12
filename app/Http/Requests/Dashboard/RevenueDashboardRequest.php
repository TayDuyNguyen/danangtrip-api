<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RevenueDashboardRequest
 * Validates request for revenue statistics.
 * (Xác thực yêu cầu thống kê doanh thu)
 */
class RevenueDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'sometimes',
                'string',
                'in:day,week,month,year',
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
            'period.in' => 'The period must be one of: day, week, month, year.',
            'from.date_format' => 'The from date must be in YYYY-MM-DD format.',
            'to.date_format' => 'The to date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The to date must be after or equal to the from date.',
        ];
    }
}
