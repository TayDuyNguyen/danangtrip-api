<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class AdminDestroyRatingRequest extends FormRequest
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
                'exists:ratings,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Rating ID is required. (ID đánh giá là bắt buộc.)',
            'id.integer' => 'Rating ID must be an integer. (ID đánh giá phải là số nguyên.)',
            'id.exists' => 'Rating not found. (Đánh giá không tồn tại.)',
        ];
    }
}
