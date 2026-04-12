<?php

namespace App\Http\Requests\Subcategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'The status field is required. (Trường trạng thái là bắt buộc.)',
        ];
    }
}
