<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class ShowContactRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:contacts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Contact ID is required. (ID liên hệ là bắt buộc.)',
            'id.integer' => 'Contact ID must be an integer. (ID liên hệ phải là số nguyên.)',
            'id.exists' => 'Contact not found. (Liên hệ không tồn tại.)',
        ];
    }
}
