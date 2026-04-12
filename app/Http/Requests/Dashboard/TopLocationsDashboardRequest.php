<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class TopLocationsDashboardRequest
 * Validates request for top locations statistics.
 * (Xác thực yêu cầu thống kê top địa điểm được yêu thích)
 */
class TopLocationsDashboardRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit must not exceed 50.',
        ];
    }
}
