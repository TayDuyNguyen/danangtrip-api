<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ReplyContactRequest
 * Validates request for replying to a contact.
 * (Xác thực yêu cầu trả lời liên hệ)
 */
class ReplyContactRequest extends FormRequest
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
                'exists:contacts,id',
            ],
            'reply' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'The contact does not exist. (Liên hệ không tồn tại.)',
            'reply.required' => 'The reply content is required. (Nội dung trả lời là bắt buộc.)',
        ];
    }
}
