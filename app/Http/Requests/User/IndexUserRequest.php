<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'role' => [
                'sometimes',
                'string',
                'in:admin,partner,user',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:active,banned',
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
            'sort_by' => [
                'sometimes',
                'string',
                'in:id,full_name,email,created_at',
            ],
            'sort_order' => [
                'sometimes',
                'string',
                'in:asc,desc',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
            'sort_by.in' => 'The sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The order must be asc or desc. (Thứ tự phải là asc hoặc desc.)',
        ];
    }
}
