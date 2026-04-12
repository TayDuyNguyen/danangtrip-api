<?php

namespace App\Http\Requests\TourCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusTourCategoryRequest extends FormRequest
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
                'exists:tour_categories,id',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tour category ID is required.',
            'id.exists' => 'The selected tour category does not exist.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
