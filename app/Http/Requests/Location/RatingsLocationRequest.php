<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class RatingsLocationRequest extends FormRequest
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
            'sort_by' => [
                'sometimes',
                'in:created_at,rating',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'page.integer' => 'The page number must be an integer. (Số trang phải là số nguyên.)',
            'page.min' => 'The page number must be at least 1. (Số trang tối thiểu là 1.)',
            'per_page.integer' => 'The items per page must be an integer. (Số lượng mỗi trang phải là số nguyên.)',
            'per_page.min' => 'The items per page must be at least 1. (Số lượng mỗi trang tối thiểu là 1.)',
            'per_page.max' => 'The items per page must not exceed 100. (Số lượng mỗi trang tối đa là 100.)',
        ];
    }
}
