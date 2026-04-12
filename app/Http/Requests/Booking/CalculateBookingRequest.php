<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CalculateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour_id' => 'required|integer|exists:tours,id',
            'tour_schedule_id' => 'required|integer|exists:tour_schedules,id',
            'quantity_adult' => 'required|integer|min:1',
            'quantity_child' => 'nullable|integer|min:0',
            'quantity_infant' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tour_id.required' => 'The tour ID is required.',
            'tour_id.integer' => 'The tour ID must be an integer.',
            'tour_id.exists' => 'The selected tour does not exist.',
            'tour_schedule_id.required' => 'The tour schedule ID is required.',
            'tour_schedule_id.integer' => 'The tour schedule ID must be an integer.',
            'tour_schedule_id.exists' => 'The selected tour schedule does not exist.',
            'quantity_adult.required' => 'The number of adults is required.',
            'quantity_adult.integer' => 'The number of adults must be an integer.',
            'quantity_adult.min' => 'The number of adults must be at least 1.',
            'quantity_child.integer' => 'The number of children must be an integer.',
            'quantity_child.min' => 'The number of children must be at least 0.',
            'quantity_infant.integer' => 'The number of infants must be an integer.',
            'quantity_infant.min' => 'The number of infants must be at least 0.',
        ];
    }
}
