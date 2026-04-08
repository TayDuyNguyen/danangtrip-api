<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:tags,name',
            ],
            'slug' => [
                'required',
                'string',
                'max:100',
                'unique:tags,slug',
            ],
            'type' => [
                'required',
                'string',
                'in:cuisine,service,feature,atmosphere',
            ],
        ];
    }

    public function messages(): array
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
