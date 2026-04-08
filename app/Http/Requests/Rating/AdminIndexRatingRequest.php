<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'in:pending,approved,rejected',
            ],
            'location_id' => [
                'sometimes',
                'integer',
                'exists:locations,id',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The status is invalid. (Trạng thái không hợp lệ.)',
            'location_id.required' => 'The location_id is required. (location_id là bắt buộc.)',
            'location_id.exists' => 'The location_id does not exist. (location_id không tồn tại.)',
            'location_id.unique' => 'You already rated this location. (Bạn đã đánh giá địa điểm này.)',
        ];
    }
}
