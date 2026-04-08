<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class IndexTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'sometimes',
                'string',
                'in:cuisine,service,feature,atmosphere',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The tag type is required.',
            'type.in' => 'The selected tag type is invalid.',
        ];
    }
}
