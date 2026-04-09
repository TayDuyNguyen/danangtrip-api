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
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'The selected role is invalid. (Vai trò được chọn không hợp lệ.)',
        ];
    }
}
