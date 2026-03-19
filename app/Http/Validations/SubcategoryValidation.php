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
     * (Lấy thông báo xác thực tùy chỉnh)
     */
    private static function messages(): array
    {
        return [
            'id.required' => 'The subcategory ID is required.',
            'id.integer' => 'The subcategory ID must be an integer.',
            'id.exists' => 'The subcategory ID does not exist.',
            'category_id.required' => 'The category_id field is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'name.required' => 'The subcategory name is required.',
            'name.max' => 'The subcategory name must not exceed 50 characters.',
            'slug.unique' => 'This slug is already taken.',
            'slug.max' => 'The slug must not exceed 60 characters.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
