<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class AdminDeleteNotificationRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:notifications,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Notification ID is required. (ID thông báo là bắt buộc.)',
            'id.integer' => 'Notification ID must be an integer. (ID thông báo phải là số nguyên.)',
            'id.exists' => 'Notification not found. (Thông báo không tồn tại.)',
        ];
    }
}
