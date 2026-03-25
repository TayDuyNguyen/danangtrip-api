<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class TagValidation
 * (Lớp xác thực cho Tag)
 */
final class TagValidation
{
    /**
     * Validate public list tags request.
     * (Xác thực yêu cầu danh sách tags công khai)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'type' => 'sometimes|string|in:cuisine,service,feature,atmosphere',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin store tag request.
     * (Xác thực yêu cầu tạo tag của admin)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:100|unique:tags,name',
                'slug' => 'required|string|max:100|unique:tags,slug',
                'type' => 'required|string|in:cuisine,service,feature,atmosphere',
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
            'name.required' => 'The tag name is required.',
            'name.unique' => 'This tag name already exists.',
            'slug.required' => 'The tag slug is required.',
            'slug.unique' => 'This tag slug already exists.',
            'type.required' => 'The tag type is required.',
            'type.in' => 'The selected tag type is invalid.',
        ];
    }
}
