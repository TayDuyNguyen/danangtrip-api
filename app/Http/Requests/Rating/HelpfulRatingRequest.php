<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class HelpfulRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'id.required' => 'The id is required. (id là bắt buộc.)',
            'id.exists' => 'The rating does not exist. (Đánh giá không tồn tại.)',
        ];
    }
}
