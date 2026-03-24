<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class BlogValidation
 * (Lớp xác thực cho các hoạt động Blog)
 */
final class BlogValidation
{
    /**
     * Validate public list blog posts request.
     * (Xác thực yêu cầu danh sách bài viết Blog công khai)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'category_id' => 'sometimes|integer|exists:blog_categories,id',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin store blog post request.
     * (Xác thực yêu cầu tạo bài viết Blog của Admin)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:200',
                'content' => 'required|string',
                'excerpt' => 'sometimes|nullable|string|max:500',
                'featured_image' => 'sometimes|nullable|string|max:255',
                'category_ids' => 'required|array',
                'category_ids.*' => 'integer|exists:blog_categories,id',
                'status' => 'sometimes|in:draft,published',
                'published_at' => 'sometimes|nullable|date',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin update blog post request.
     * (Xác thực yêu cầu cập nhật bài viết Blog của Admin)
     */
    public static function validateUpdate(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'title' => 'sometimes|string|max:200',
                'content' => 'sometimes|string',
                'excerpt' => 'sometimes|nullable|string|max:500',
                'featured_image' => 'sometimes|nullable|string|max:255',
                'category_ids' => 'sometimes|array',
                'category_ids.*' => 'integer|exists:blog_categories,id',
                'status' => 'sometimes|in:draft,published',
                'published_at' => 'sometimes|nullable|date',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin publish/unpublish blog post request.
     * (Xác thực yêu cầu xuất bản/ẩn bài viết Blog của Admin)
     */
    public static function validatePublish(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'status' => 'required|in:draft,published',
            ],
            self::messages()
        );
    }

    /**
     * Centralized validation error messages.
     * (Thông báo lỗi xác thực tập trung)
     */
    protected static function messages(): array
    {
        return [
            'title.required' => 'The blog title is required.',
            'content.required' => 'The blog content is required.',
            'category_ids.required' => 'At least one category is required.',
            'category_ids.*.exists' => 'One or more selected categories are invalid.',
            'status.in' => 'The selected status is invalid.',
            'published_at.date' => 'The published date is not a valid date.',
            'category_id.exists' => 'The selected category is invalid.',
        ];
    }
}
