<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionsSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'max:255',
            ],
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required. (Từ khóa tìm kiếm là bắt buộc.)',
            'q.min' => 'The search query must be at least 2 characters. (Từ khóa tìm kiếm phải có ít nhất 2 ký tự.)',
            'q.max' => 'The search query must not exceed 255 characters. (Từ khóa tìm kiếm không được vượt quá 255 ký tự.)',
            'limit.max' => 'The limit is too large. (Giới hạn quá lớn.)',
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
        ];
    }
}
