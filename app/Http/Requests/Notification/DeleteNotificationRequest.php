<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class DeleteNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Xác định xem người dùng có quyền thực hiện yêu cầu này không)
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     * (Lấy các quy tắc xác thực áp dụng cho yêu cầu)
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:notifications,id',
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
            'id.required' => 'Notification ID is required. (ID thông báo là bắt buộc.)',
            'id.integer' => 'Notification ID must be an integer. (ID thông báo phải là số nguyên.)',
            'id.exists' => 'Selected notification ID is invalid. (ID thông báo đã chọn không hợp lệ.)',
        ];
    }
}
