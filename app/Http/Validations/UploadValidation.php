<?php

namespace App\Http\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Class UploadValidation
 * @package App\Http\Validations
 */
final class UploadValidation
{
    /**
     * Validate the request for uploading a single image.
     * (Xác thực yêu cầu tải lên một ảnh)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateUploadImage(Request $request): ValidatorInstance
    {
        return Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,webp|max:5120',
            'folder' => 'nullable|string|max:255',
        ], self::messages());
    }

    /**
     * Validate the request for uploading multiple images.
     * (Xác thực yêu cầu tải lên nhiều ảnh)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateUploadImages(Request $request): ValidatorInstance
    {
        return Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,webp|max:5120',
            'folder' => 'nullable|string|max:255',
        ], self::messages());
    }

    /**
     * Validate the request for deleting an image.
     * (Xác thực yêu cầu xóa ảnh)
     *
     * @param Request $request
     * @return ValidatorInstance
     */
    public static function validateDeleteImage(Request $request): ValidatorInstance
    {
        return Validator::make($request->all(), [
            'public_id' => 'required|string',
        ], self::messages());
    }

    /**
     * Get the validation messages.
     * (Lấy các thông báo xác thực)
     *
     * @return array
     */
    protected static function messages(): array
    {
        return [
            'image.required' => 'An image file is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Only JPEG, PNG, and WEBP images are allowed.',
            'image.max' => 'The image may not be greater than 5MB.',
            'images.required' => 'An array of image files is required.',
            'images.array' => 'The images must be an array.',
            'images.max' => 'You can upload a maximum of 10 images at a time.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Only JPEG, PNG, and WEBP images are allowed for each file.',
            'images.*.max' => 'Each image may not be greater than 5MB.',
            'folder.string' => 'The folder name must be a string.',
            'folder.max' => 'The folder name may not be greater than 255 characters.',
            'public_id.required' => 'The public_id is required to delete an image.',
            'public_id.string' => 'The public_id must be a string.',
        ];
    }
}
