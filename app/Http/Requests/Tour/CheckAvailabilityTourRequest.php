<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityTourRequest extends FormRequest
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
                'exists:tours,id',
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tour ID is required.',
            'id.integer' => 'Tour ID must be an integer.',
            'id.exists' => 'The selected tour does not exist.',
            'date.required' => 'Date is required.',
            'date.date' => 'Invalid date format.',
            'date.after_or_equal' => 'The date must be today or in the future.',
        ];
    }
}
