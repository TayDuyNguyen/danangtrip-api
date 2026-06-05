<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLandingPageStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be draft or published.',
        ];
    }
}
