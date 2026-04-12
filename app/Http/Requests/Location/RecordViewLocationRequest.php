<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class RecordViewLocationRequest extends FormRequest
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
            'session_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'id.exists' => 'The location ID does not exist. (Mã địa điểm không tồn tại.)',
            'session_id.required' => 'The session ID is required. (Mã phiên là bắt buộc.)',
            'session_id.string' => 'The session ID must be a string. (Mã phiên phải là chuỗi ký tự.)',
            'session_id.max' => 'The session ID must not exceed 255 characters. (Mã phiên không được vượt quá 255 ký tự.)',
        ];
    }
}
