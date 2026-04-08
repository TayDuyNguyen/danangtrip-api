<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
            'name' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
                'unique:categories,slug,1,id',
            ],
            'icon' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'image' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'sort_order' => [
                'sometimes',
                'nullable',
                'integer',
            ],
            'status' => [
                'sometimes',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The category ID is required. (Mã danh mục là bắt buộc.)',
            'id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'name.required' => 'The category name is required. (Tên danh mục là bắt buộc.)',
            'name.max' => 'The category name must not exceed 50 characters. (Tên danh mục không được vượt quá 50 ký tự.)',
            'name.string' => 'The category name must be a string. (Tên danh mục phải là chuỗi ký tự.)',
            'slug.unique' => 'This slug is already taken. (Slug này đã tồn tại.)',
            'slug.max' => 'The slug must not exceed 60 characters. (Slug không được vượt quá 60 ký tự.)',
            'icon.max' => 'The icon name must not exceed 50 characters. (Tên icon không được vượt quá 50 ký tự.)',
            'icon.string' => 'The icon name must be a string. (Tên icon phải là chuỗi ký tự.)',
            'description.string' => 'The description must be a string. (Mô tả phải là chuỗi ký tự.)',
            'image.max' => 'The image URL must not exceed 255 characters. (URL hình ảnh không được vượt quá 255 ký tự.)',
            'image.string' => 'The image must be a string URL. (Hình ảnh phải là chuỗi ký tự URL.)',
            'sort_order.integer' => 'The sort order must be an integer. (Thứ tự sắp xếp phải là số nguyên.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'The status field is required. (Trường trạng thái là bắt buộc.)',
        ];
    }
}
