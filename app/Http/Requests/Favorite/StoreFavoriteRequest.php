<?php

namespace App\Http\Requests\Favorite;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreFavoriteRequest.
 * (Yêu cầu Lưu yêu thích)
 */
class StoreFavoriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Xác định xem người dùng có được phép thực hiện yêu cầu này không)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * (Lấy các quy tắc xác thực áp dụng cho yêu cầu)
     */
    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * (Lấy các thông báo lỗi cho các quy tắc xác thực đã định nghĩa)
     */
    public function messages(): array
    {
        return [
            'location_id.required' => 'The location ID is required. (Mã địa điểm là bắt buộc.)',
            'location_id.integer' => 'The location ID must be an integer. (Mã địa điểm phải là số nguyên.)',
            'location_id.exists' => 'The location does not exist. (Địa điểm không tồn tại.)',
        ];
    }
}
