<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class PatchStatusLocationRequest extends FormRequest
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
            'status' => [
                'required',
                'in:active,inactive,pending',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'Status is required. (Trạng thái là bắt buộc.)',
        ];
    }
}
