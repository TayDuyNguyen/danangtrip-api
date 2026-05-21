<?php

namespace App\Http\Requests\Tour;

use Illuminate\Foundation\Http\FormRequest;

class ShowTourBySlugRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->route('slug'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug format is invalid. (Định dạng slug không hợp lệ.)',
        ];
    }
}
