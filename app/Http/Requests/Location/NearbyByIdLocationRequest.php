<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class NearbyByIdLocationRequest extends FormRequest
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
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn tối thiểu là 1.)',
            'limit.max' => 'The limit must not exceed 100. (Giới hạn tối đa là 100.)',
        ];
    }
}
