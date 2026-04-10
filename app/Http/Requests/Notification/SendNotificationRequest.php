<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
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
                'required',
                'integer',
                'exists:users,id',
            ],
            'type' => [
                'required',
                'string',
                'max:30',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
            'data' => [
                'sometimes',
                'array',
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
            'user_id.required' => 'User ID is required. (ID người dùng là bắt buộc.)',
            'user_id.exists' => 'The selected user is invalid. (Người dùng được chọn không hợp lệ.)',
            'type.required' => 'Notification type is required. (Loại thông báo là bắt buộc.)',
            'title.required' => 'Title is required. (Tiêu đề là bắt buộc.)',
            'content.required' => 'Content is required. (Nội dung là bắt buộc.)',
        ];
    }
}
