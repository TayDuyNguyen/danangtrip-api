<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UserRatingsRequest
 * Validates request for fetching a user's ratings.
 * (Xác thực yêu cầu lấy danh sách đánh giá của người dùng)
 */
class UserRatingsRequest extends FormRequest
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
                'exists:users,id',
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
            'id.required' => 'The user ID is required. (Mã người dùng là bắt buộc.)',
            'id.integer' => 'The user ID must be an integer. (Mã người dùng phải là số nguyên.)',
            'id.exists' => 'The user ID does not exist. (Mã người dùng không tồn tại.)',
        ];
    }
}
