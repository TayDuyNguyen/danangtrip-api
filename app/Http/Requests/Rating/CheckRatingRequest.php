<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class CheckRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'required_without_all:tour_id,booking_id',
                'integer',
                'exists:locations,id',
            ],
            'tour_id' => [
                'required_without_all:location_id,booking_id',
                'integer',
                'exists:tours,id',
            ],
            'booking_id' => [
                'required_without_all:location_id,tour_id',
                'integer',
                'exists:bookings,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required_without_all' => 'Ban phai cung cap location_id, tour_id hoac booking_id. (You must provide location_id, tour_id, or booking_id.)',
            'tour_id.required_without_all' => 'Ban phai cung cap location_id, tour_id hoac booking_id. (You must provide location_id, tour_id, or booking_id.)',
            'booking_id.required_without_all' => 'Ban phai cung cap location_id, tour_id hoac booking_id. (You must provide location_id, tour_id, or booking_id.)',
            'location_id.exists' => 'The location does not exist.',
            'tour_id.exists' => 'The tour does not exist.',
            'booking_id.exists' => 'The booking does not exist.',
        ];
    }
}
