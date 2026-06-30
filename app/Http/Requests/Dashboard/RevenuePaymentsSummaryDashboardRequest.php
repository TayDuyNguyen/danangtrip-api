<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class RevenuePaymentsSummaryDashboardRequest extends FormRequest
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
            'payment_gateway' => [
                'sometimes',
                'string',
                'nullable',
            ],
        ];
    }
}
