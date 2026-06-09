<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class SendChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'locale' => ['sometimes', 'string', 'in:vi,en'],
            'session_id' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'The chat message is required. (Nội dung chat là bắt buộc.)',
            'message.max' => 'The chat message must not exceed 1000 characters. (Nội dung chat không được vượt quá 1000 ký tự.)',
            'locale.in' => 'The locale must be vi or en. (Ngôn ngữ phải là vi hoặc en.)',
            'session_id.max' => 'The session_id must not exceed 100 characters. (session_id không được vượt quá 100 ký tự.)',
        ];
    }
}
