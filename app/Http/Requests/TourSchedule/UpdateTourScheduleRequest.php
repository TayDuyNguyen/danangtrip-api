<?php

namespace App\Http\Requests\TourSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:tour_schedules,id',
            ],
            'start_date' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'end_date' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'max_people' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'price_adult' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_child' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'price_infant' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:available,full,cancelled',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Schedule ID is required.',
            'id.exists' => 'The selected schedule does not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.date_format' => 'Start date must be in Y-m-d format.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.required' => 'End date is required.',
            'end_date.date_format' => 'End date must be in Y-m-d format.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'max_people.required' => 'Max people is required.',
            'status.in' => 'Status must be available, full, or cancelled.',
        ];
    }
}
