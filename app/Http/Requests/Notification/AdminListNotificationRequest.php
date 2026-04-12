<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class AdminListNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Xác định xem người dùng có quyền thực hiện yêu cầu này không)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * (Lấy các quy tắc xác thực áp dụng cho yêu cầu)
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'sometimes',
                'integer',
                'exists:users,id',
            ],
            'type' => [
                'sometimes',
                'string',
                'max:30',
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

    /**
     * Get the error messages for the defined validation rules.
     * (Lấy các thông báo lỗi cho các quy tắc xác thực đã định nghĩa)
     */
    public function messages(): array
    {
        return [
            'user_id.integer' => 'User ID must be an integer. (ID người dùng phải là số nguyên.)',
            'user_id.exists' => 'Selected user ID is invalid. (ID người dùng đã chọn không hợp lệ.)',
            'type.string' => 'Notification type must be a string. (Loại thông báo phải là chuỗi.)',
            'type.max' => 'Notification type may not be greater than 30 characters. (Loại thông báo không được vượt quá 30 ký tự.)',
            'page.integer' => 'The page number must be an integer. (Số trang phải là số nguyên.)',
            'per_page.max' => 'The items per page may not be greater than 100. (Số bản ghi mỗi trang không được vượt quá 100.)',
        ];
    }
}
