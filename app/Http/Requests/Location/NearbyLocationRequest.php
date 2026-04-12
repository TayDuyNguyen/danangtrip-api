<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;

class NearbyLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'radius' => [
                'sometimes',
                'numeric',
                'min:0.1',
                'max:50',
            ],
            'limit' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
            'sort_by' => [
                'sometimes',
                'in:avg_rating,review_count,view_count,created_at,price_min',
            ],
            'sort_order' => [
                'sometimes',
                'in:asc,desc',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required for nearby search. (Vĩ độ là bắt buộc để tìm kiếm lân cận.)',
            'lat.numeric' => 'Latitude must be a number. (Vĩ độ phải là số.)',
            'lat.between' => 'Latitude must be between -90 and 90. (Vĩ độ phải nằm trong khoảng -90 đến 90.)',
            'lng.required' => 'Longitude is required for nearby search. (Kinh độ là bắt buộc để tìm kiếm lân cận.)',
            'lng.numeric' => 'Longitude must be a number. (Kinh độ phải là số.)',
            'lng.between' => 'Longitude must be between -180 and 180. (Kinh độ phải nằm trong khoảng -180 đến 180.)',
            'radius.numeric' => 'The radius must be a number. (Bán kính phải là số.)',
            'radius.min' => 'The radius must be at least 0.1 km. (Bán kính tối thiểu là 0.1 km.)',
            'radius.max' => 'The radius must not exceed 50 km. (Bán kính tối đa là 50 km.)',
            'limit.integer' => 'The limit must be an integer. (Giới hạn phải là số nguyên.)',
            'limit.min' => 'The limit must be at least 1. (Giới hạn tối thiểu là 1.)',
            'limit.max' => 'The limit must not exceed 100. (Giới hạn tối đa là 100.)',
            'sort_by.in' => 'The selected sort field is invalid. (Trường sắp xếp không hợp lệ.)',
            'sort_order.in' => 'The selected sort order is invalid. (Thứ tự sắp xếp không hợp lệ.)',
        ];
    }
}
