<?php

namespace App\Http\Requests\Amenity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateAmenityRequest
 * Validates request for updating an amenity.
 * (Xác thực yêu cầu cập nhật tiện ích)
 */
class UpdateAmenityRequest extends FormRequest
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
                'exists:amenities,id',
            ],
            'name' => [
                'sometimes',
                'required_without_all:icon,category',
                'string',
                'max:50',
                Rule::unique('amenities', 'name')->ignore($this->route('id')),
            ],
            'icon' => [
                'sometimes',
                'required_without_all:name,category',
                'string',
                'max:50',
            ],
            'category' => [
                'sometimes',
                'required_without_all:name,icon',
                'string',
                'max:30',
                'in:connectivity,parking,comfort,payment',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'The amenity does not exist. (Tiện ích không tồn tại.)',
            'name.unique' => 'This amenity name already exists. (Tên tiện ích này đã tồn tại.)',
            'name.max' => 'The amenity name must not exceed 100 characters. (Tên tiện ích không được vượt quá 100 ký tự.)',
            'icon.max' => 'The icon must not exceed 100 characters. (Icon không được vượt quá 100 ký tự.)',
            'category.in' => 'The selected amenity category is invalid. (Danh mục tiện ích được chọn không hợp lệ.)',
        ];
    }
}
