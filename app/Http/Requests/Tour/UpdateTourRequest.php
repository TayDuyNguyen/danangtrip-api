<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                'max:200',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:220',
                'unique:tours,slug,1,id',
            ],
            'tour_category_id' => [
                'sometimes',
                'integer',
                'exists:tour_categories,id',
            ],
            'description' => [
                'sometimes',
                'string',
            ],
            'short_desc' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'itinerary' => [
                'sometimes',
                'array',
            ],
            'inclusions' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'exclusions' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'price_adult' => [
                'sometimes',
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
            'discount_percent' => [
                'sometimes',
                'integer',
                'min:0',
                'max:100',
            ],
            'duration' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'start_time' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'meeting_point' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'max_people' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'min_people' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'available_from' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'available_to' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'thumbnail' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'images' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'video_url' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'location_ids' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'status' => [
                'sometimes',
                'in:available,unavailable,pending',
            ],
            'is_featured' => [
                'sometimes',
                'boolean',
            ],
            'is_hot' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tour ID is required.',
            'id.integer' => 'Tour ID must be an integer.',
            'id.exists' => 'The selected tour does not exist.',
            'name.required' => 'Tour name is required.',
            'name.string' => 'Tour name must be a string.',
            'name.max' => 'Tour name may not be greater than 200 characters.',
            'slug.string' => 'Slug must be a string.',
            'slug.max' => 'Slug may not be greater than 220 characters.',
            'slug.unique' => 'This slug is already in use.',
            'tour_category_id.required' => 'Category is required.',
            'tour_category_id.integer' => 'Category ID must be an integer.',
            'tour_category_id.exists' => 'The selected category does not exist.',
            'description.required' => 'Description is required.',
            'description.string' => 'Description must be a string.',
            'short_desc.string' => 'Short description must be a string.',
            'short_desc.max' => 'Short description may not be greater than 500 characters.',
            'itinerary.required' => 'Itinerary is required.',
            'itinerary.array' => 'Itinerary must be an array.',
            'price_adult.required' => 'Adult price is required.',
            'price_adult.numeric' => 'Adult price must be a number.',
            'price_adult.min' => 'Adult price must be at least 0.',
            'duration.required' => 'Duration is required.',
            'duration.string' => 'Duration must be a string.',
            'status.in' => 'Invalid status value.',
            'is_featured.boolean' => 'Featured flag must be a boolean.',
            'is_hot.boolean' => 'Hot flag must be a boolean.',
        ];
    }
}
