<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateBookingStatusRequest
 * Validates the request to update a booking status.
 * (Xác thực yêu cầu cập nhật trạng thái đơn đặt tour)
 */
class UpdateBookingStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Kiểm tra xem người dùng có quyền thực hiện yêu cầu này không)
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
            'booking_status' => ['required', 'string', Rule::in(BookingStatus::values())],
            'cancellation_reason' => ['sometimes', 'required_if:booking_status,'.BookingStatus::CANCELLED->value, 'string', 'max:1000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * (Lấy các thông báo lỗi cho các quy tắc xác thực đã định nghĩa)
     */
    public function messages(): array
    {
        return [
            'booking_status.required' => 'The booking status is required.',
            'booking_status.string' => 'The booking status must be a string.',
            'booking_status.in' => 'The selected booking status is invalid.',
        ];
    }
}
