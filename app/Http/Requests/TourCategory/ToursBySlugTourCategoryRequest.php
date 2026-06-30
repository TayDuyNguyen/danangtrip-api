<?php

namespace App\Http\Requests\TourCategory;

use App\Enums\TourBookingAvailability;
use Illuminate\Foundation\Http\FormRequest;

class ToursBySlugTourCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
        ]);

        if ($this->input('sort_by') === 'price') {
            $this->merge(['sort_by' => 'price_adult']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'exists:tour_categories,slug',
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
            'sort_by' => [
                'sometimes',
                'string',
                'in:created_at,price_adult,rating_avg,price,view_count,name,booking_count',
            ],
            'sort_order' => [
                'sometimes',
                'string',
                'in:asc,desc',
            ],
            'booking_availability' => [
                'sometimes',
                'string',
                'in:'.implode(',', TourBookingAvailability::values()),
            ],
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
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Slug is required.',
            'slug.exists' => 'The selected slug does not exist.',
            'booking_availability.in' => 'Invalid booking availability value.',
        ];
    }
}
