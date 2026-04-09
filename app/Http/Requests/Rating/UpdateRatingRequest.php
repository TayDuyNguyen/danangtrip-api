<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingRequest extends FormRequest
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
            'score' => [
                'sometimes',
                'integer',
                'between:1,5',
            ],
            'comment' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'images' => [
                'sometimes',
                'array',
                'max:5',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'The id is required. (id là bắt buộc.)',
            'id.exists' => 'The rating does not exist. (Đánh giá không tồn tại.)',
            'score.required' => 'The score is required. (Điểm đánh giá là bắt buộc.)',
            'score.between' => 'The score must be between 1 and 5. (Điểm đánh giá phải từ 1 đến 5.)',
            'images.array' => 'Images must be an array. (Images phải là một mảng.)',
            'images.max' => 'Images must not exceed 5 files. (Tối đa 5 ảnh.)',
            'images.*.image' => 'Each file must be an image. (Mỗi file phải là ảnh.)',
            'images.*.max' => 'Each image must not exceed 5MB. (Mỗi ảnh tối đa 5MB.)',
        ];
    }
}
