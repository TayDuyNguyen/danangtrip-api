<?php

namespace App\Http\Requests\TourSchedule;

use Illuminate\Foundation\Http\FormRequest;

class ShowTourScheduleRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Schedule ID is required.',
            'id.exists' => 'The selected schedule does not exist.',
        ];
    }
}
