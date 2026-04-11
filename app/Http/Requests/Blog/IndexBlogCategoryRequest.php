<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for listing blog categories.
 * (Yêu cầu danh sách danh mục blog)
 */
class IndexBlogCategoryRequest extends FormRequest
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
        return [];
    }

    /**
     * Get custom error messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    public function messages(): array
    {
        return [];
    }
}
