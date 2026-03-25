<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class AmenityValidation
 * (Lớp xác thực cho Tiện ích)
 */
final class AmenityValidation
{
    /**
     * Validate public list amenities request.
     * (Xác thực yêu cầu danh sách tiện ích công khai)
     */
    public static function validateIndex(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'category' => 'sometimes|string|in:connectivity,parking,comfort,payment',
            ],
            self::messages()
        );
    }

    /**
     * Validate admin store amenity request.
     * (Xác thực yêu cầu tạo tiện ích của admin)
     */
    public static function validateStore(Request $request): ValidatorInstance
    {
        return Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:100|unique:amenities,name',
                'icon' => 'sometimes|nullable|string|max:100',
                'category' => 'required|string|in:connectivity,parking,comfort,payment',
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
            'name.required' => 'The amenity name is required.',
            'name.unique' => 'This amenity name already exists.',
            'category.required' => 'The amenity category is required.',
            'category.in' => 'The selected amenity category is invalid.',
        ];
    }
}
