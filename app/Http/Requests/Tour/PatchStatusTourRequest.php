<?php

namespace App\Http\Requests\Tour;

use App\Enums\TourStatus;
use Illuminate\Foundation\Http\FormRequest;

class PatchStatusTourRequest extends FormRequest
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
            'status' => [
                'required',
                'in:'.implode(',', TourStatus::values()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tour ID is required.',
            'id.integer' => 'Tour ID must be an integer.',
            'id.exists' => 'The selected tour does not exist.',
            'status.in' => 'Invalid status value.',
        ];
    }
}
