<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BookingReportsDashboardRequest
 * Validates request for booking reports.
 * (Xác thực yêu cầu báo cáo đơn hàng)
 */
class BookingReportsDashboardRequest extends FormRequest
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
                'in:pending,confirmed,completed,cancelled',
            ],
            'payment_status' => [
                'sometimes',
                'string',
                'in:pending,paid,refunded,failed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'The from date must be in YYYY-MM-DD format.',
            'to.date_format' => 'The to date must be in YYYY-MM-DD format.',
            'to.after_or_equal' => 'The to date must be after or equal to the from date.',
            'status.in' => 'The selected booking status is invalid.',
            'payment_status.in' => 'The selected payment status is invalid.',
        ];
    }
}
