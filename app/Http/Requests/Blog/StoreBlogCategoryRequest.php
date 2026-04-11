<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request for creating a new blog category.
 * (Yêu cầu tạo danh mục blog mới)
 */
class StoreBlogCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:blog_categories,name',
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:60',
                'unique:blog_categories,slug',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
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
            'name.required' => 'The category name is required. (Tên danh mục là bắt buộc)',
            'name.max' => 'The category name may not be greater than 50 characters. (Tên danh mục không được vượt quá 50 ký tự)',
            'name.unique' => 'The category name has already been taken. (Tên danh mục đã được sử dụng)',
            'slug.max' => 'The slug may not be greater than 60 characters. (Slug không được vượt quá 60 ký tự)',
            'slug.unique' => 'The slug has already been taken. (Slug đã được sử dụng)',
        ];
    }
}
