<?php

namespace App\Http\Requests\Tour;

use App\Enums\TourBookingAvailability;
use App\Enums\TourStatus;
use Illuminate\Foundation\Http\FormRequest;

class IndexTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('status') === 'sold_out') {
            $this->merge([
                'booking_availability' => TourBookingAvailability::SOLD_OUT->value,
            ]);
            $this->request->remove('status');
        }
    }

    public function rules(): array
    {
        return [
            'price_min' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'price_max' => [
                'sometimes',
                'integer',
                'gte:price_min',
            ],
            'duration' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'available_from' => [
                'sometimes',
                'date_format:Y-m-d',
            ],
            'available_to' => [
                'sometimes',
                'date_format:Y-m-d',
                'after_or_equal:available_from',
            ],
            'tour_category_id' => [
                'sometimes',
                'integer',
                'exists:tour_categories,id',
            ],
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                'in:'.implode(',', TourStatus::values()),
            ],
            'booking_availability' => [
                'sometimes',
                'in:'.implode(',', TourBookingAvailability::values()),
            ],
            'is_featured' => [
                'sometimes',
                'boolean',
            ],
            'is_hot' => [
                'sometimes',
                'boolean',
            ],
            'sort_by' => [
                'sometimes',
                'in:created_at,price_adult,view_count,name,rating_avg,booking_count',
            ],
            'sort_order' => [
                'sometimes',
                'in:asc,desc',
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
            'tour_category_id.required' => 'Category is required.',
            'tour_category_id.integer' => 'Category ID must be an integer.',
            'tour_category_id.exists' => 'The selected category does not exist.',
            'status.in' => 'Invalid status value.',
            'booking_availability.in' => 'Invalid booking availability value.',
            'is_featured.boolean' => 'Featured flag must be a boolean.',
            'is_hot.boolean' => 'Hot flag must be a boolean.',
            'per_page.max' => 'Items per page may not be greater than 100.',
        ];
    }
}
