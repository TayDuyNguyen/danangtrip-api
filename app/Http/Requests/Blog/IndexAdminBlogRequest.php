<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for listing admin blog posts with filters.
 * (Yêu cầu danh sách bài viết blog admin với bộ lọc)
 */
class IndexAdminBlogRequest extends FormRequest
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
                'sometimes',
                'string',
                'in:draft,published,archived',
            ],
            'category_id' => [
                'sometimes',
                'integer',
                'exists:blog_categories,id',
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
     * Get custom error messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The status must be one of: draft, published, or archived. (Trạng thái phải là một trong: draft, published, hoặc archived)',
            'category_id.exists' => 'The selected category does not exist. (Danh mục được chọn không tồn tại)',
            'page.integer' => 'The page must be an integer. (Trang phải là số nguyên)',
            'per_page.integer' => 'The per page must be an integer. (Số lượng mỗi trang phải là số nguyên)',
            'per_page.max' => 'The per page may not be greater than 100. (Số lượng mỗi trang không được lớn hơn 100)',
        ];
    }
}
