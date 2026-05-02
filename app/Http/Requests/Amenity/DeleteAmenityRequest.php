<?php

namespace App\Http\Requests\Amenity;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:amenities,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Amenity ID is required. (ID tiện ích là bắt buộc.)',
            'id.integer' => 'Amenity ID must be an integer. (ID tiện ích phải là số nguyên.)',
            'id.exists' => 'Amenity not found. (Tiện ích không tồn tại.)',
        ];
    }
}
