<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Tag ID is required. (ID tag là bắt buộc.)',
            'id.integer' => 'Tag ID must be an integer. (ID tag phải là số nguyên.)',
            'id.exists' => 'Tag not found. (Tag không tồn tại.)',
        ];
    }
}
