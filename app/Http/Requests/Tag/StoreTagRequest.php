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
                'max:50',
                'unique:tags,name',
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
                'unique:tags,slug',
            ],
            'type' => [
                'required',
                'string',
                'max:30',
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
