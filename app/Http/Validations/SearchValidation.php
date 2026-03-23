<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class SearchValidation
 * Provides centralized validation logic for search endpoints.
 * (Cung cấp logic xác thực tập trung cho các API tìm kiếm)
 */
final class SearchValidation
{
    /**
     * Validate search request.
     * (Xác thực yêu cầu tìm kiếm)
     */
    public static function validateSearch(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'q' => 'required|string|min:2|max:255',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'subcategory_id' => 'sometimes|integer|exists:subcategories,id',
                'district' => 'sometimes|string|max:50',
                'price_level' => 'sometimes|integer|between:1,4',
                'price_min' => 'sometimes|numeric|min:0',
                'price_max' => 'sometimes|numeric|min:0',
                'rating_min' => 'sometimes|numeric|min:0|max:5',
                'tag' => 'sometimes|string|max:255',
                'sort' => 'sometimes|in:avg_rating,review_count,view_count,created_at,price_min,price_max,name',
                'order' => 'sometimes|in:asc,desc',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'session_id' => 'sometimes|string|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate suggestions request.
     * (Xác thực yêu cầu gợi ý tìm kiếm)
     */
    public static function validateSuggestions(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'q' => 'required|string|max:255',
                'limit' => 'sometimes|integer|min:1|max:20',
            ],
            self::messages()
        );
    }

    /**
     * Validate popular keywords request.
     * (Xác thực yêu cầu từ khóa phổ biến)
     */
    public static function validatePopular(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'limit' => 'sometimes|integer|min:1|max:50',
                'days' => 'sometimes|integer|min:1|max:365',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    private static function messages(): array
    {
        return [
            'q.required' => 'The search query is required. (Từ khóa tìm kiếm là bắt buộc.)',
            'q.min' => 'The search query must be at least 2 characters. (Từ khóa tìm kiếm phải có ít nhất 2 ký tự.)',
            'q.max' => 'The search query must not exceed 255 characters. (Từ khóa tìm kiếm không được vượt quá 255 ký tự.)',
            'category_id.integer' => 'The category ID must be an integer. (Mã danh mục phải là số nguyên.)',
            'category_id.exists' => 'The category ID does not exist. (Mã danh mục không tồn tại.)',
            'subcategory_id.integer' => 'The subcategory ID must be an integer. (Mã danh mục con phải là số nguyên.)',
            'subcategory_id.exists' => 'The subcategory ID does not exist. (Mã danh mục con không tồn tại.)',
            'district.max' => 'The district must not exceed 50 characters. (Quận/huyện không được vượt quá 50 ký tự.)',
            'price_level.between' => 'The price level must be between 1 and 4. (Mức giá phải nằm trong khoảng 1 đến 4.)',
            'price_min.min' => 'The minimum price must be at least 0. (Giá tối thiểu phải >= 0.)',
            'price_max.min' => 'The maximum price must be at least 0. (Giá tối đa phải >= 0.)',
            'rating_min.max' => 'The minimum rating must be at most 5. (Điểm đánh giá tối thiểu phải <= 5.)',
            'tag.max' => 'The tag filter must not exceed 255 characters. (Bộ lọc tag không được vượt quá 255 ký tự.)',
            'sort.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'order.in' => 'The selected order is invalid. (Kiểu sắp xếp không hợp lệ.)',
            'per_page.max' => 'The per_page must not exceed 100. (per_page không được vượt quá 100.)',
            'session_id.max' => 'The session_id must not exceed 100 characters. (session_id không được vượt quá 100 ký tự.)',
            'limit.max' => 'The limit is too large. (Giới hạn quá lớn.)',
            'days.max' => 'The days value is too large. (Giá trị days quá lớn.)',
        ];
    }
}
