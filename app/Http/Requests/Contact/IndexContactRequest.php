<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexContactRequest
 * Validates request for listing contacts.
 * (Xác thực yêu cầu lấy danh sách liên hệ)
 */
class IndexContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:new,read,replied',
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
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
        ];
    }
}
