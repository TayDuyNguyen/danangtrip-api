<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExportContactRequest
 * Validates request for exporting contacts to Excel.
 * (Xác thực yêu cầu xuất danh sách liên hệ ra Excel)
 */
class ExportContactRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
        ];
    }
}
