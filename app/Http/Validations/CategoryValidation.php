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
     * Validate show category request
     * (Xác thực yêu cầu hiển thị)
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
     * Get custom validation messages.
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    private static function messages(): array
    {
        return [
            'id.required' => 'The category ID is required.',
            'id.integer' => 'The category ID must be an integer.',
            'id.exists' => 'The category ID does not exist.',
            'name.required' => 'The category name is required.',
            'name.max' => 'The category name must not exceed 50 characters.',
            'slug.unique' => 'This slug is already taken.',
            'slug.max' => 'The slug must not exceed 60 characters.',
            'icon.max' => 'The icon name must not exceed 50 characters.',
            'image.max' => 'The image URL must not exceed 255 characters.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
