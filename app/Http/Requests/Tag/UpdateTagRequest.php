<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateTagRequest
 * Validates request for updating a tag.
 * (Xác thực yêu cầu cập nhật tag)
 */
class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:tags,id',
            ],
            'name' => [
                'sometimes',
                'required_without_all:slug,type',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->ignore($this->route('id')),
            ],
            'slug' => [
                'sometimes',
                'required_without_all:name,type',
                'string',
                'max:60',
                Rule::unique('tags', 'slug')->ignore($this->route('id')),
            ],
            'type' => [
                'sometimes',
                'required_without_all:name,slug',
                'string',
                'max:30',
                'in:cuisine,service,feature,atmosphere',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'The tag does not exist. (Tag không tồn tại.)',
            'name.unique' => 'This tag name already exists. (Tên tag này đã tồn tại.)',
            'name.max' => 'The tag name must not exceed 100 characters. (Tên tag không được vượt quá 100 ký tự.)',
            'slug.unique' => 'This tag slug already exists. (Slug tag này đã tồn tại.)',
            'slug.max' => 'The tag slug must not exceed 100 characters. (Slug tag không được vượt quá 100 ký tự.)',
            'type.in' => 'The selected tag type is invalid. (Loại tag được chọn không hợp lệ.)',
        ];
    }
}
