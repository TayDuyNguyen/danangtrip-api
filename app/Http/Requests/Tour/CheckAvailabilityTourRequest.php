<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityTourRequest extends FormRequest
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
                'exists:tours,id',
            ],
            'schedule_id' => [
                'required',
                'integer',
                'exists:tour_schedules,id',
            ],
            'quantity_adult' => [
                'required',
                'integer',
                'min:1',
            ],
            'quantity_child' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'quantity_infant' => [
                'sometimes',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tour ID is required.',
            'id.integer' => 'Tour ID must be an integer.',
            'id.exists' => 'The selected tour does not exist.',
            'schedule_id.required' => 'Schedule ID is required.',
            'schedule_id.exists' => 'The selected schedule does not exist.',
            'quantity_adult.required' => 'Adult quantity is required.',
            'quantity_adult.min' => 'Adult quantity must be at least 1.',
        ];
    }
}
