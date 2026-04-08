<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class PatchFeaturedLocationRequest extends FormRequest
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
            'is_featured' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'is_featured.required' => 'The is_featured field is required. (Trường nổi bật là bắt buộc.)',
        ];
    }
}
