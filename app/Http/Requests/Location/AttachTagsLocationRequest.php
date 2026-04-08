<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class AttachTagsLocationRequest extends FormRequest
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
            'tag_ids' => [
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
            'tag_ids.required' => 'At least one tag ID is required. (Ít nhất một mã tag là bắt buộc.)',
            'tag_ids.array' => 'Tag IDs must be an array. (Danh sách mã tag phải là một mảng.)',
            'tag_ids.*.exists' => 'One or more tag IDs are invalid. (Một hoặc nhiều mã tag không hợp lệ.)',
        ];
    }
}
