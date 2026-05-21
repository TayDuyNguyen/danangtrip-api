<?php

namespace App\Http\Requests\TourSchedule;

use App\Enums\TourScheduleStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusTourScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:tour_schedules,id',
            ],
            'status' => [
                'required',
                'string',
                'in:'.implode(',', TourScheduleStatus::values()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Schedule ID is required.',
            'id.exists' => 'The selected schedule does not exist.',
            'status.in' => 'Status must be available or cancelled.',
        ];
    }
}
