<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class IndexLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],
            'subcategory_id' => [
                'sometimes',
                'integer',
                'exists:subcategories,id',
            ],
            'district' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'price_level' => [
                'sometimes',
                'integer',
                'between:1,4',
            ],
            'is_featured' => [
                'sometimes',
                'boolean',
            ],
            'sort_by' => [
                'sometimes',
                'in:avg_rating,review_count,view_count,created_at,price_min',
            ],
            'sort_order' => [
                'sometimes',
                'in:asc,desc',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/Huyện không được vượt quá 50 ký tự.)',
            'search.max' => 'The search term must not exceed 100 characters. (Từ khóa tìm kiếm không được vượt quá 100 ký tự.)',
            'price_level.integer' => 'The price level must be an integer. (Mức giá phải là số nguyên.)',
            'price_level.between' => 'The price level must be between 1 and 4. (Mức giá phải nằm trong khoảng từ 1 đến 4.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The selected sort order is invalid. (Thứ tự sắp xếp không hợp lệ.)',
            'per_page.integer' => 'The items per page must be an integer. (Số lượng mỗi trang phải là số nguyên.)',
            'per_page.min' => 'The items per page must be at least 1. (Số lượng mỗi trang tối thiểu là 1.)',
            'per_page.max' => 'The items per page must not exceed 100. (Số lượng mỗi trang tối đa là 100.)',
        ];
    }
}
