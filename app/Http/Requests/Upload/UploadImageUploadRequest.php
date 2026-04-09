<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
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
            'image.required' => 'An image file is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Only JPEG, PNG, and WEBP images are allowed.',
            'image.max' => 'The image may not be greater than 5MB.',
            'folder.string' => 'The folder name must be a string.',
            'folder.max' => 'The folder name may not be greater than 255 characters.',
            'folder.regex' => 'The folder may contain only letters, numbers, slashes, dashes and underscores.',
        ];
    }
}
