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
            'date_from' => [
                'sometimes',
                'date',
            ],
            'date_to' => [
                'sometimes',
                'date',
                'after_or_equal:date_from',
            ],
            'status' => [
                'sometimes',
                'in:pending,approved,rejected',
            ],
            'is_new' => [
                'sometimes',
                'boolean',
            ],
            'type' => [
                'sometimes',
                'in:location,tour',
            ],
            'location_id' => [
                'sometimes',
                'integer',
                'exists:locations,id',
            ],
            'tour_id' => [
                'sometimes',
                'integer',
                'exists:tours,id',
            ],
            'score' => [
                'sometimes',
                'integer',
                'min:1',
                'max:5',
            ],
            'search' => [
                'sometimes',
                'string',
                'nullable',
            ],
            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
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
