<?php

namespace App\Http\Requests\Favorite;

use Illuminate\Foundation\Http\FormRequest;

class CheckFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'required_without:tour_id',
                'integer',
                'exists:locations,id',
            ],
            'tour_id' => [
                'required_without:location_id',
                'integer',
                'exists:tours,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required_without' => 'The location ID or tour ID is required. (Mã địa điểm hoặc mã tour là bắt buộc.)',
            'location_id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'location_id.exists' => 'The location does not exist. (Địa điểm không tồn tại.)',
            'tour_id.required_without' => 'The location ID or tour ID is required. (Mã địa điểm hoặc mã tour là bắt buộc.)',
            'tour_id.integer' => 'The tour ID must be an integer. (Mã tour phải là số nguyên.)',
            'tour_id.exists' => 'The tour does not exist. (Tour không tồn tại.)',
        ];
    }
}
