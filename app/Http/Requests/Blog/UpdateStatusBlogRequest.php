<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for updating blog post status.
 * (Yêu cầu cập nhật trạng thái bài viết blog)
 */
class UpdateStatusBlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Xác định người dùng có quyền thực hiện yêu cầu này)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * (Lấy các quy tắc validation áp dụng cho request)
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:draft,published,archived',
            ],
        ];
    }

    /**
     * Get custom error messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The status is required. (Trạng thái là bắt buộc)',
            'status.in' => 'The status must be one of: draft, published, or archived. (Trạng thái phải là một trong: draft, published, hoặc archived)',
        ];
    }
}
