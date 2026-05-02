<?php

namespace App\Http\Requests\District;

use Illuminate\Foundation\Http\FormRequest;

class IndexDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
