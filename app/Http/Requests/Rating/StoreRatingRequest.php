<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
                Rule::unique('ratings', 'location_id')->where(function ($query) {
                    return $query->where('user_id', auth('api')->id());
                }),
            ],
            'score' => [
                'required',
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
            'location_id.required' => 'The location_id is required. (location_id là bắt buộc.)',
            'location_id.exists' => 'The location_id does not exist. (location_id không tồn tại.)',
            'location_id.unique' => 'You already rated this location. (Bạn đã đánh giá địa điểm này.)',
            'score.required' => 'The score is required. (Điểm đánh giá là bắt buộc.)',
            'score.between' => 'The score must be between 1 and 5. (Điểm đánh giá phải từ 1 đến 5.)',
            'images.array' => 'Images must be an array. (Images phải là một mảng.)',
            'images.max' => 'Images must not exceed 5 files. (Tối đa 5 ảnh.)',
            'images.*.image' => 'Each file must be an image. (Mỗi file phải là ảnh.)',
            'images.*.max' => 'Each image must not exceed 5MB. (Mỗi ảnh tối đa 5MB.)',
        ];
    }
}
