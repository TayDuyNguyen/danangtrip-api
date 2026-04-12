<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class DetachAmenityLocationRequest extends FormRequest
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
                'exists:locations,id',
            ],
            'amenity_id' => [
                'required',
                'integer',
                'exists:amenities,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
        ];
    }
}
