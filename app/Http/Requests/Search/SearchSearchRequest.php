<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class SearchSearchRequest
 * Validates unified search request parameters for locations and tours.
 * (Xác thực tham số tìm kiếm thống nhất cho địa điểm và tour)
 */
class SearchSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type', 'location');

        $common = [
            'q' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['sometimes', 'string', 'in:location,tour'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'session_id' => ['sometimes', 'string', 'max:100'],
        ];

        // Location: sort_by / sort_order  (matches IndexLocationRequest + LocationRepository)
        $locationOnly = [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['sometimes', 'integer', 'exists:subcategories,id'],
            'district' => ['sometimes', 'string', 'max:50'],
            'price_min' => ['sometimes', 'numeric', 'min:0'],
            'price_max' => ['sometimes', 'numeric', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'avg_rating', 'review_count', 'view_count', 'price_min'])],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];

        // Tour: order_by / order_dir  (matches IndexTourRequest + TourRepository)
        $tourOnly = [
            'tour_category_id' => ['sometimes', 'integer', 'exists:tour_categories,id'],
            'price_min' => ['sometimes', 'numeric', 'min:0'],
            'price_max' => ['sometimes', 'numeric', 'min:0'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_hot' => ['sometimes', 'boolean'],
            'order_by' => ['sometimes', 'string', Rule::in(['created_at', 'price_adult', 'view_count', 'name', 'rating_avg'])],
            'order_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ];

        return array_merge($common, $type === 'tour' ? $tourOnly : $locationOnly);
    }

    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required. (Từ khóa tìm kiếm là bắt buộc.)',
            'q.min' => 'The search query must be at least 1 character. (Từ khóa tìm kiếm phải có ít nhất 1 ký tự.)',
            'q.max' => 'The search query must not exceed 255 characters. (Từ khóa tìm kiếm không được vượt quá 255 ký tự.)',
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'tour_category_id.exists' => 'The tour category ID does not exist. (Mã danh mục tour không tồn tại.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/huyện không được vượt quá 50 ký tự.)',
            'price_min.min' => 'The minimum price must be at least 0. (Giá tối thiểu phải >= 0.)',
            'price_max.min' => 'The maximum price must be at least 0. (Giá tối đa phải >= 0.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The selected sort order is invalid. (Kiểu sắp xếp không hợp lệ.)',
            'order_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'order_dir.in' => 'The selected sort order is invalid. (Kiểu sắp xếp không hợp lệ.)',
            'page.min' => 'The page must be at least 1. (Trang phải lớn hơn hoặc bằng 1.)',
            'per_page.max' => 'The per_page must not exceed 100. (per_page không được vượt quá 100.)',
            'is_featured.boolean' => 'The featured filter must be a boolean. (Bộ lọc nổi bật phải là kiểu boolean.)',
            'is_hot.boolean' => 'The hot filter must be a boolean. (Bộ lọc hot phải là kiểu boolean.)',
            'session_id.max' => 'The session_id must not exceed 100 characters. (session_id không được vượt quá 100 ký tự.)',
        ];
    }
}
