<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class BookingTrendDashboardRequest
 * Validates request for booking trend statistics.
 * (Xác thực yêu cầu thống kê xu hướng đặt tour)
 */
class BookingTrendDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => [
                'sometimes',
                'integer',
                'min:1',
                'max:365',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'days.integer' => 'The days must be an integer.',
            'days.min' => 'The days must be at least 1.',
            'days.max' => 'The days must not exceed 365.',
        ];
    }
}
