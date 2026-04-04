<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class CategoryValidation
 * Provides centralized validation logic for category management.
 * (Cung cấp logic xác thực tập trung cho quản lý danh mục)
 */
final class CategoryValidation
{
    /**
     * Validate show category request.
     * (Xác thực yêu cầu chi tiết danh mục)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:categories,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate store category request.
     * (Xác thực yêu cầu tạo danh mục)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:50',
                'slug' => 'sometimes|nullable|string|max:60|unique:categories,slug',
                'icon' => 'sometimes|nullable|string|max:50',
                'description' => 'sometimes|nullable|string',
                'image' => 'sometimes|nullable|string|max:255',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate update category request.
     * (Xác thực yêu cầu cập nhật danh mục)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:categories,id',
                'name' => 'sometimes|string|max:50',
                'slug' => 'sometimes|nullable|string|max:60|unique:categories,slug,'.$id.',id',
                'icon' => 'sometimes|nullable|string|max:50',
                'description' => 'sometimes|nullable|string',
                'image' => 'sometimes|nullable|string|max:255',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate delete category request.
     * (Xác thực yêu cầu xóa danh mục)
     */
    public static function validateDelete(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:categories,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate update category status request.
     * (Xác thực yêu cầu đổi trạng thái danh mục)
     */
    public static function validateUpdateStatus(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'status' => 'required|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    protected static function messages(): array
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
