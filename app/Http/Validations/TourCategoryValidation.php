<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class TourCategoryValidation
 * Provides centralized validation logic for tour category management.
 * (Cung cấp logic xác thực tập trung cho quản lý danh mục tour)
 */
final class TourCategoryValidation
{
    /**
     * Validate show tour category request.
     * (Xác thực yêu cầu chi tiết danh mục tour)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:tour_categories,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate tours by category slug request.
     * (Xác thực yêu cầu danh sách tour theo slug danh mục)
     */
    public static function validateToursBySlug(string $slug, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['slug' => $slug]),
            [
                'slug' => 'required|string|exists:tour_categories,slug',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'sort' => 'sometimes|string|in:created_at,price,rating_avg',
                'order' => 'sometimes|string|in:asc,desc',
            ],
            self::messages()
        );
    }

    /**
     * Validate store tour category request.
     * (Xác thực yêu cầu tạo danh mục tour)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:100',
                'slug' => 'sometimes|nullable|string|max:120|unique:tour_categories,slug',
                'description' => 'sometimes|nullable|string',
                'icon' => 'sometimes|nullable|string|max:50',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate update tour category request.
     * (Xác thực yêu cầu cập nhật danh mục tour)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tour_categories,id',
                'name' => 'sometimes|string|max:100',
                'slug' => 'sometimes|nullable|string|max:120|unique:tour_categories,slug,'.$id.',id',
                'description' => 'sometimes|nullable|string',
                'icon' => 'sometimes|nullable|string|max:50',
                'sort_order' => 'sometimes|nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Validate status update.
     * (Xác thực yêu cầu cập nhật trạng thái)
     */
    public static function validateUpdateStatus(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tour_categories,id',
                'status' => 'required|in:active,inactive',
            ],
            self::messages()
        );
    }

    /**
     * Get custom error messages.
     * (Lấy thông báo lỗi tùy chỉnh)
     */
    protected static function messages(): array
    {
        return [
            'id.required' => 'Tour category ID is required.',
            'id.exists' => 'The selected tour category does not exist.',
            'slug.required' => 'Slug is required.',
            'slug.exists' => 'The selected slug does not exist.',
            'name.required' => 'Tour category name is required.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active or inactive.',
        ];
    }
}
