<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class AvatarProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'The avatar file is required.',
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'The avatar must be a file of type: jpeg, png, jpg.',
            'avatar.max' => 'The avatar size must not exceed 2MB.',
        ];
    }
}
