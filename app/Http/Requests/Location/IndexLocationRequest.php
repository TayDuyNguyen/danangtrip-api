<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class IndexLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if (! $this->has('search') && $this->has('q')) {
            $merged['search'] = $this->query('q');
        }
        if (! $this->has('sort_by') && $this->has('sort')) {
            $merged['sort_by'] = $this->query('sort');
        }
        if (! $this->has('sort_order') && $this->has('order')) {
            $merged['sort_order'] = $this->query('order');
        }
        if (! $this->has('category_ids') && $this->has('categories')) {
            $rawCategories = (string) $this->query('categories');
            $merged['category_ids'] = array_values(array_filter(array_map('trim', explode(',', $rawCategories)), fn ($v) => $v !== ''));
        }
        if ($this->has('districts')) {
            $rawDistricts = $this->query('districts');
            if (is_string($rawDistricts)) {
                $merged['districts'] = array_values(array_filter(array_map('trim', explode(',', $rawDistricts)), fn ($v) => $v !== ''));
            }
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],
            'category_ids' => [
                'sometimes',
                'array',
            ],
            'category_ids.*' => [
                'required',
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
            'districts' => [
                'sometimes',
                'array',
            ],
            'districts.*' => [
                'required',
                'string',
                'max:50',
            ],
            'search' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'q' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'categories' => [
                'sometimes',
                'string',
            ],
            'price_level' => [
                'sometimes',
                'integer',
                'between:1,4',
            ],
            'min_rating' => [
                'sometimes',
                'numeric',
                'between:1,5',
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

    public function messages(): array
    {
        return [
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'category_ids.array' => 'The category_ids must be an array. (category_ids phải là mảng.)',
            'category_ids.*.integer' => 'Each category ID must be an integer. (Mỗi category_id phải là số nguyên.)',
            'category_ids.*.exists' => 'One or more category IDs do not exist. (Một hoặc nhiều category_id không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/Huyện không được vượt quá 50 ký tự.)',
            'districts.array' => 'The districts must be an array. (districts phải là mảng.)',
            'districts.*.string' => 'Each district must be a string. (Mỗi quận/huyện phải là chuỗi.)',
            'districts.*.max' => 'Each district must not exceed 50 characters. (Mỗi quận/huyện không vượt quá 50 ký tự.)',
            'search.max' => 'The search term must not exceed 100 characters. (Từ khóa tìm kiếm không được vượt quá 100 ký tự.)',
            'q.max' => 'The q term must not exceed 100 characters. (Từ khóa q không được vượt quá 100 ký tự.)',
            'price_level.integer' => 'The price level must be an integer. (Mức giá phải là số nguyên.)',
            'price_level.between' => 'The price level must be between 1 and 4. (Mức giá phải nằm trong khoảng từ 1 đến 4.)',
            'min_rating.numeric' => 'The min_rating must be a number. (min_rating phải là số.)',
            'min_rating.between' => 'The min_rating must be between 1 and 5. (min_rating phải nằm trong khoảng 1 đến 5.)',
            'is_featured.boolean' => 'The is_featured field must be true or false. (Trường nổi bật phải là true hoặc false.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The selected sort order is invalid. (Thứ tự sắp xếp không hợp lệ.)',
            'page.integer' => 'The page must be an integer. (Trang phải là số nguyên.)',
            'page.min' => 'The page must be at least 1. (Trang phải lớn hơn hoặc bằng 1.)',
            'per_page.integer' => 'The items per page must be an integer. (Số lượng mỗi trang phải là số nguyên.)',
            'per_page.min' => 'The items per page must be at least 1. (Số lượng mỗi trang tối thiểu là 1.)',
            'per_page.max' => 'The items per page must not exceed 100. (Số lượng mỗi trang tối đa là 100.)',
        ];
    }
}
