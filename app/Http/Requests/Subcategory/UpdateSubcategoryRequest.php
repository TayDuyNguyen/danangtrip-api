<?php

namespace App\Http\Requests\Subcategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubcategoryRequest extends FormRequest
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
                'exists:subcategories,id',
            ],
            'category_id' => [
                'sometimes',
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
                'unique:subcategories,slug,1,id',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
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
            'id.required' => 'The subcategory ID is required. (Mã danh mục con là bắt buộc.)',
            'id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'category_id.required' => 'The category ID is required. (Mã danh mục chính là bắt buộc.)',
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục chính phải là số nguyên.)',
            'category_id.exists' => 'The selected category does not exist. (Danh mục được chọn không tồn tại.)',
            'name.required' => 'The subcategory name is required. (Tên danh mục con là bắt buộc.)',
            'name.max' => 'The subcategory name must not exceed 50 characters. (Tên danh mục con không được vượt quá 50 ký tự.)',
            'name.string' => 'The subcategory name must be a string. (Tên danh mục con phải là chuỗi ký tự.)',
            'slug.unique' => 'This slug is already taken. (Slug này đã tồn tại.)',
            'slug.max' => 'The slug must not exceed 60 characters. (Slug không được vượt quá 60 ký tự.)',
            'description.string' => 'The description must be a string. (Mô tả phải là chuỗi ký tự.)',
            'sort_order.integer' => 'The sort order must be an integer. (Thứ tự sắp xếp phải là số nguyên.)',
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'status.required' => 'The status field is required. (Trường trạng thái là bắt buộc.)',
        ];
    }
}
