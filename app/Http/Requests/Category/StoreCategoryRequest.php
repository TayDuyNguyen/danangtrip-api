<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
                'unique:categories,slug',
            ],
            'icon' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'icon_background' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/',
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
            'name.required' => 'The category name is required. (Tên danh mục là bắt buộc.)',
            'name.max' => 'The category name must not exceed 50 characters. (Tên danh mục không được vượt quá 50 ký tự.)',
            'name.string' => 'The category name must be a string. (Tên danh mục phải là chuỗi ký tự.)',
            'slug.unique' => 'This slug is already taken. (Slug này đã tồn tại.)',
            'slug.max' => 'The slug must not exceed 60 characters. (Slug không được vượt quá 60 ký tự.)',
            'icon.max' => 'The icon name must not exceed 50 characters. (Tên icon không được vượt quá 50 ký tự.)',
            'icon.string' => 'The icon name must be a string. (Tên icon phải là chuỗi ký tự.)',
            'icon_background.max' => 'The icon background must not exceed 20 characters. (Màu nền icon không được vượt quá 20 ký tự.)',
            'icon_background.regex' => 'The icon background must be a valid hex color. (Màu nền icon phải là mã hex hợp lệ.)',
            'description.string' => 'The description must be a string. (Mô tả phải là chuỗi ký tự.)',
            'image.max' => 'The image URL must not exceed 255 characters. (URL hình ảnh không được vượt quá 255 ký tự.)',
            'image.string' => 'The image must be a string URL. (Hình ảnh phải là chuỗi ký tự URL.)',
            'sort_order.integer' => 'The sort order must be an integer. (Thứ tự sắp xếp phải là số nguyên.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'The status field is required. (Trường trạng thái là bắt buộc.)',
        ];
    }
}
