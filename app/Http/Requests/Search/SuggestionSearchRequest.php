<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SuggestionSearchRequest
 * Validates search suggestion requests.
 * (Xác thực yêu cầu gợi ý tìm kiếm)
 */
class SuggestionSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'min:1',
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required. (Từ khóa tìm kiếm là bắt buộc.)',
            'q.string' => 'The search query must be a string. (Từ khóa tìm kiếm phải là chuỗi ký tự.)',
            'q.min' => 'The search query must be at least 1 character. (Từ khóa tìm kiếm phải có ít nhất 1 ký tự.)',
            'q.max' => 'The search query must not exceed 255 characters. (Từ khóa tìm kiếm không được vượt quá 255 ký tự.)',
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn phải ít nhất là 1.)',
            'limit.max' => 'The limit must not exceed 20. (Giới hạn không được vượt quá 20.)',
        ];
    }
}
