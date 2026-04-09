<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class AttachAmenitiesLocationRequest extends FormRequest
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
                'exists:locations,id',
            ],
            'amenity_ids' => [
                'required',
                'array',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'amenity_ids.required' => 'At least one amenity ID is required. (Ít nhất một mã tiện ích là bắt buộc.)',
            'amenity_ids.array' => 'Amenity IDs must be an array. (Danh sách mã tiện ích phải là một mảng.)',
            'amenity_ids.*.exists' => 'One or more amenity IDs are invalid. (Một hoặc nhiều mã tiện ích không hợp lệ.)',
        ];
    }
}
