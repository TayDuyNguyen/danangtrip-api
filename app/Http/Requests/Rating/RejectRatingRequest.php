<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class RejectRatingRequest extends FormRequest
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
            'rejected_reason' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The id is required. (id là bắt buộc.)',
            'id.exists' => 'The rating does not exist. (Đánh giá không tồn tại.)',
            'rejected_reason.required' => 'The rejected_reason is required. (Lý do từ chối là bắt buộc.)',
            'rejected_reason.max' => 'The rejected_reason must not exceed 255 characters. (Lý do từ chối tối đa 255 ký tự.)',
        ];
    }
}
