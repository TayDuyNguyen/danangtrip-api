<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class SubcategoryValidation
 * Provides centralized validation logic for subcategory management.
 * (Cung cấp logic xác thực tập trung cho quản lý danh mục con)
 */
final class SubcategoryValidation
{
    /**
     * Validate show subcategory request.
     * (Xác thực yêu cầu chi tiết danh mục con)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:subcategories,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate store subcategory request.
     * (Xác thực yêu cầu tạo danh mục con)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'category_id' => 'required|integer|exists:categories,id',
                'name' => 'required|string|max:50',
                'slug' => 'sometimes|nullable|string|max:60|unique:subcategories,slug',
                'description' => 'sometimes|nullable|string',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate update subcategory request.
     * (Xác thực yêu cầu cập nhật danh mục con)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:subcategories,id',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'name' => 'sometimes|string|max:50',
                'slug' => 'sometimes|nullable|string|max:60|unique:subcategories,slug,'.$id.',id',
                'description' => 'sometimes|nullable|string',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate delete subcategory request.
     * (Xác thực yêu cầu xóa danh mục con)
     */
    public static function validateDelete(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:subcategories,id',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    protected static function messages(): array
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
            'status.in' => 'The selected status is invalid. (Trạng thái được chọn không hợp lệ.)',
            'sort_order.integer' => 'The sort order must be an integer. (Thứ tự sắp xếp phải là số nguyên.)',
        ];
    }
}
