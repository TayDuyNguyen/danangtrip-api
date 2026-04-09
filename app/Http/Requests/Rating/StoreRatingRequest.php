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
                'required_without_all:tour_id,booking_id',
                'integer',
                'exists:locations,id',
                Rule::unique('ratings', 'location_id')->where(function ($query) {
                    return $query->where('user_id', auth('api')->id())->whereNotNull('location_id');
                }),
            ],
            'tour_id' => [
                'required_without_all:location_id,booking_id',
                'integer',
                'exists:tours,id',
                Rule::unique('ratings', 'tour_id')->where(function ($query) {
                    return $query->where('user_id', auth('api')->id())->whereNotNull('tour_id');
                }),
            ],
            'booking_id' => [
                'required_without_all:location_id,tour_id',
                'integer',
                'exists:bookings,id',
                Rule::unique('ratings', 'booking_id')->where(function ($query) {
                    return $query->where('user_id', auth('api')->id())->whereNotNull('booking_id');
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
            'location_id.required_without_all' => 'Bạn phải chọn location_id, tour_id, hoặc booking_id.',
            'tour_id.required_without_all' => 'Bạn phải chọn location_id, tour_id, hoặc booking_id.',
            'booking_id.required_without_all' => 'Bạn phải chọn location_id, tour_id, hoặc booking_id.',
            'location_id.exists' => 'The location_id does not exist. (location_id không tồn tại.)',
            'tour_id.exists' => 'The tour_id does not exist.',
            'booking_id.exists' => 'The booking_id does not exist.',
            'location_id.unique' => 'You already rated this location. (Bạn đã đánh giá địa điểm này.)',
            'tour_id.unique' => 'You already rated this tour.',
            'booking_id.unique' => 'You already rated this booking.',
            'score.required' => 'The score is required. (Điểm đánh giá là bắt buộc.)',
            'score.between' => 'The score must be between 1 and 5. (Điểm đánh giá phải từ 1 đến 5.)',
            'images.array' => 'Images must be an array. (Images phải là một mảng.)',
            'images.max' => 'Images must not exceed 5 files. (Tối đa 5 ảnh.)',
            'images.*.image' => 'Each file must be an image. (Mỗi file phải là ảnh.)',
            'images.*.max' => 'Each image must not exceed 5MB. (Mỗi ảnh tối đa 5MB.)',
        ];
    }
}
