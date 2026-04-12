<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ExportUserRequest
 * Validates request for exporting users to Excel.
 * (Xác thực yêu cầu xuất danh sách người dùng ra Excel)
 */
class ExportUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => [
                'sometimes',
                'string',
                'in:admin,staff,user',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:active,banned',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
        ];
    }
}
