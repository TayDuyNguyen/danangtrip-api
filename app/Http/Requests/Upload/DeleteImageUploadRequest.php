<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class DeleteImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_id' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'public_id.required' => 'The public_id is required to delete an image.',
            'public_id.string' => 'The public_id must be a string.',
        ];
    }
}
