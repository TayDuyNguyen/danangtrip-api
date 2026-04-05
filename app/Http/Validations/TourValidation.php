<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class TourValidation
 * Provides centralized validation logic for tour management.
 * (Cung cấp logic xác thực tập trung cho quản lý tour)
 */
final class TourValidation
{
    /**
     * Validate show tour request.
     * (Xác thực yêu cầu chi tiết tour)
     */
    public static function validateShow(int $id): ValidatorInstance
    {
        return Validator::make(
            ['id' => $id],
            [
                'id' => 'required|integer|exists:tours,id',
            ],
            self::messages()
        );
    }

    /**
     * Validate tour ratings request.
     * (Xác thực yêu cầu đánh giá tour)
     */
    public static function validateRatings(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tours,id',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate featured tours request.
     * (Xác thực yêu cầu tour nổi bật)
     */
    public static function validateFeatured(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'limit' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate hot tours request.
     * (Xác thực yêu cầu tour hot)
     */
    public static function validateHot(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'limit' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate check availability request.
     * (Xác thực yêu cầu kiểm tra còn chỗ)
     */
    public static function validateCheckAvailability(int $id, Request $request): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tours,id',
                'date' => 'required|date|after_or_equal:today',
            ],
            self::messages()
        );
    }

    /**
     * Validate list tours request.
     * (Xác thực yêu cầu danh sách tour)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'tour_category_id' => 'sometimes|integer|exists:tour_categories,id',
                'search' => 'sometimes|string|max:100',
                'status' => 'sometimes|in:available,unavailable,pending,active,inactive',
                'is_featured' => 'sometimes|boolean',
                'is_hot' => 'sometimes|boolean',
                'order_by' => 'sometimes|in:created_at,price_adult,rating_avg',
                'order_dir' => 'sometimes|in:asc,desc',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate store tour request.
     * (Xác thực yêu cầu tạo tour)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:200',
                'slug' => 'sometimes|nullable|string|max:220|unique:tours,slug',
                'tour_category_id' => 'required|integer|exists:tour_categories,id',
                'description' => 'required|string',
                'short_desc' => 'sometimes|nullable|string|max:500',
                'itinerary' => 'required|array',
                'inclusions' => 'sometimes|nullable|array',
                'exclusions' => 'sometimes|nullable|array',
                'price_adult' => 'required|numeric|min:0',
                'price_child' => 'sometimes|nullable|numeric|min:0',
                'price_infant' => 'sometimes|nullable|numeric|min:0',
                'discount_percent' => 'sometimes|integer|min:0|max:100',
                'duration' => 'required|string|max:100',
                'start_time' => 'sometimes|nullable|string|max:100',
                'meeting_point' => 'sometimes|nullable|string|max:255',
                'max_people' => 'sometimes|integer|min:1',
                'min_people' => 'sometimes|integer|min:1',
                'available_from' => 'sometimes|nullable|date',
                'available_to' => 'sometimes|nullable|date',
                'thumbnail' => 'sometimes|nullable|string|max:255',
                'images' => 'sometimes|nullable|array',
                'video_url' => 'sometimes|nullable|string|max:255',
                'location_ids' => 'sometimes|nullable|array',
                'status' => 'sometimes|in:available,unavailable,pending,active,inactive',
                'is_featured' => 'sometimes|boolean',
                'is_hot' => 'sometimes|boolean',
            ],
            self::messages()
        );
    }

    /**
     * Validate update tour request.
     * (Xác thực yêu cầu cập nhật tour)
     */
    public static function validateUpdate(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tours,id',
                'name' => 'sometimes|string|max:200',
                'slug' => 'sometimes|nullable|string|max:220|unique:tours,slug,'.$id.',id',
                'tour_category_id' => 'sometimes|integer|exists:tour_categories,id',
                'description' => 'sometimes|string',
                'short_desc' => 'sometimes|nullable|string|max:500',
                'itinerary' => 'sometimes|array',
                'inclusions' => 'sometimes|nullable|array',
                'exclusions' => 'sometimes|nullable|array',
                'price_adult' => 'sometimes|numeric|min:0',
                'price_child' => 'sometimes|nullable|numeric|min:0',
                'price_infant' => 'sometimes|nullable|numeric|min:0',
                'discount_percent' => 'sometimes|integer|min:0|max:100',
                'duration' => 'sometimes|string|max:100',
                'start_time' => 'sometimes|nullable|string|max:100',
                'meeting_point' => 'sometimes|nullable|string|max:255',
                'max_people' => 'sometimes|integer|min:1',
                'min_people' => 'sometimes|integer|min:1',
                'available_from' => 'sometimes|nullable|date',
                'available_to' => 'sometimes|nullable|date',
                'thumbnail' => 'sometimes|nullable|string|max:255',
                'images' => 'sometimes|nullable|array',
                'video_url' => 'sometimes|nullable|string|max:255',
                'location_ids' => 'sometimes|nullable|array',
                'status' => 'sometimes|in:available,unavailable,pending',
                'is_featured' => 'sometimes|boolean',
                'is_hot' => 'sometimes|boolean',
            ],
            self::messages()
        );
    }

    /**
     * Validate status update.
     * (Xác thực yêu cầu cập nhật trạng thái)
     */
    public static function validatePatchStatus(Request $request, int $id): ValidatorInstance
    {
        return Validator::make(
            array_merge($request->all(), ['id' => $id]),
            [
                'id' => 'required|integer|exists:tours,id',
                'status' => 'required|in:available,unavailable,pending,active,inactive',
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
            'id.required' => 'Tour ID is required.',
            'id.integer' => 'Tour ID must be an integer.',
            'id.exists' => 'The selected tour does not exist.',
            'name.required' => 'Tour name is required.',
            'name.string' => 'Tour name must be a string.',
            'name.max' => 'Tour name may not be greater than 200 characters.',
            'slug.string' => 'Slug must be a string.',
            'slug.max' => 'Slug may not be greater than 220 characters.',
            'slug.unique' => 'This slug is already in use.',
            'tour_category_id.required' => 'Category is required.',
            'tour_category_id.integer' => 'Category ID must be an integer.',
            'tour_category_id.exists' => 'The selected category does not exist.',
            'description.required' => 'Description is required.',
            'description.string' => 'Description must be a string.',
            'short_desc.string' => 'Short description must be a string.',
            'short_desc.max' => 'Short description may not be greater than 500 characters.',
            'itinerary.required' => 'Itinerary is required.',
            'itinerary.array' => 'Itinerary must be an array.',
            'price_adult.required' => 'Adult price is required.',
            'price_adult.numeric' => 'Adult price must be a number.',
            'price_adult.min' => 'Adult price must be at least 0.',
            'duration.required' => 'Duration is required.',
            'duration.string' => 'Duration must be a string.',
            'status.in' => 'Invalid status value.',
            'is_featured.boolean' => 'Featured flag must be a boolean.',
            'is_hot.boolean' => 'Hot flag must be a boolean.',
            'date.required' => 'Date is required.',
            'date.date' => 'Invalid date format.',
            'date.after_or_equal' => 'The date must be today or in the future.',
            'per_page.max' => 'Items per page may not be greater than 100.',
        ];
    }
}
