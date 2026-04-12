<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => [
                'required',
                'array',
                'max:10',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
            'folder' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_\\/\\-]+\$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'An array of image files is required.',
            'images.array' => 'The images must be an array.',
            'images.max' => 'You can upload a maximum of 10 images at a time.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Only JPEG, PNG, and WEBP images are allowed for each file.',
            'images.*.max' => 'Each image may not be greater than 5MB.',
            'folder.string' => 'The folder name must be a string.',
            'folder.max' => 'The folder name may not be greater than 255 characters.',
            'folder.regex' => 'The folder may contain only letters, numbers, slashes, dashes and underscores.',
        ];
    }
}
