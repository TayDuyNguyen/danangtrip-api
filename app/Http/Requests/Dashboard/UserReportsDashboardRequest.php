<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class UserReportsDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => [
                'sometimes',
                'integer',
                'min:2000',
                'max:2027',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'year.integer' => 'The year must be an integer.',
            'year.min' => 'The year must be at least 2000.',
            'year.max' => 'The year cannot be in the far future.',
        ];
    }
}
