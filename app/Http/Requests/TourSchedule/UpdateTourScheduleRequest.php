<?php

namespace App\Http\Requests\TourSchedule;

use App\Enums\TourScheduleBookingAvailability;
use App\Enums\TourScheduleStatus;
use App\Models\TourSchedule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTourScheduleRequest extends FormRequest
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
            'start_date' => [
                'sometimes',
                'date',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    $schedule = TourSchedule::find($this->route('id'));
                    if ($schedule && $schedule->start_date) {
                        $today = date('Y-m-d');
                        $currentDate = $schedule->start_date->format('Y-m-d');
                        // If the existing schedule starts in the past, bypass the future check.
                        if ($currentDate < $today) {
                            return;
                        }
                        // If it is a future schedule and the start date is changed to a past date, fail.
                        if ($value !== $currentDate && $value < $today) {
                            $fail('Start date must be today or in the future.');
                        }
                    }
                },
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
            'departure_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'departure_place' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'booking_deadline' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:'.implode(',', TourScheduleStatus::values()),
            ],
            'booking_availability' => [
                'sometimes',
                'string',
                'in:'.implode(',', TourScheduleBookingAvailability::values()),
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
            'departure_code.max' => 'Departure code may not be greater than 50 characters.',
            'departure_place.max' => 'Departure place may not be greater than 255 characters.',
            'booking_deadline.date' => 'Booking deadline must be a valid date.',
            'status.in' => 'Status must be available or cancelled.',
            'booking_availability.in' => 'Booking availability must be open or sold_out.',
        ];
    }
}
