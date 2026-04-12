<?php

namespace App\Http\Requests\TourSchedule;

use Illuminate\Foundation\Http\FormRequest;

class IndexTourScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_id' => [
                'sometimes',
                'integer',
                'exists:tours,id',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:available,full,cancelled',
            ],
            'from' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
            ],
            'to' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:from',
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

    public function messages(): array
    {
        return [
            'tour_id.required' => 'Tour ID is required.',
            'tour_id.exists' => 'The selected tour does not exist.',
            'status.in' => 'Status must be available, full, or cancelled.',
            'from.date_format' => 'From date must be in Y-m-d format.',
            'to.date_format' => 'To date must be in Y-m-d format.',
        ];
    }
}
