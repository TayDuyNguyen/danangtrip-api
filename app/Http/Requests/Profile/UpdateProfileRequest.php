<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^\\+?[0-9\\s\\-\\.]{10,15}\$/',
            ],
            'birthdate' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
            ],
            'gender' => [
                'sometimes',
                'nullable',
                'in:male,female,other',
            ],
            'city' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.string' => 'The full name must be a string.',
            'full_name.max' => 'The full name must not exceed 100 characters.',
            'phone.max' => 'The phone number must not exceed 20 characters.',
            'phone.regex' => 'The phone number format is invalid.',
            'birthdate.date' => 'The birthdate must be a valid date.',
            'gender.in' => 'The selected gender is invalid.',
            'city.max' => 'The city name must not exceed 50 characters.',
        ];
    }
}
