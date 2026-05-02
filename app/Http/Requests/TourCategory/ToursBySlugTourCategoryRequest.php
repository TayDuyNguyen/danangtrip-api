<?php

namespace App\Http\Requests\TourCategory;

use App\Enums\TourBookingAvailability;
use Illuminate\Foundation\Http\FormRequest;

class ToursBySlugTourCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
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
                'in:created_at,price_adult,rating_avg,price',
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
