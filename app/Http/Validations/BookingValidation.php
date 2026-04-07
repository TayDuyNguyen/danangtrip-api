<?php

namespace App\Http\Validations;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class BookingValidation
{
    /**
     * Validate data for calculating booking price.
     */
    public static function validateCalculate(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'tour_id' => 'required|integer|exists:tours,id',
            'tour_schedule_id' => 'required|integer|exists:tour_schedules,id',
            'quantity_adult' => 'required|integer|min:1',
            'quantity_child' => 'nullable|integer|min:0',
            'quantity_infant' => 'nullable|integer|min:0',
        ], self::messages());
    }

    /**
     * Validate data for creating a new booking.
     */
    public static function validateStore(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'tour_id' => 'required|integer|exists:tours,id',
            'tour_schedule_id' => 'required|integer|exists:tour_schedules,id',
            'quantity_adult' => 'required|integer|min:1',
            'quantity_child' => 'nullable|integer|min:0',
            'quantity_infant' => 'nullable|integer|min:0',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:255',
            'customer_note' => 'nullable|string',
            'payment_method' => ['required', 'string', Rule::in(PaymentMethod::values())],
        ], self::messages());
    }

    /**
     * Validate data for canceling a booking.
     */
    public static function validateCancel(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'cancellation_reason' => 'required|string|max:1000',
        ], self::messages());
    }

    /**
     * Validate data for updating a booking's status.
     */
    public static function validateUpdateStatus(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'booking_status' => ['required', 'string', Rule::in(BookingStatus::values())],
        ], self::messages());
    }

    /**
     * Validate data for updating a booking's payment status.
     */
    public static function validateUpdatePaymentStatus(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'payment_status' => ['required', 'string', Rule::in(PaymentStatus::values())],
        ], self::messages());
    }

    /**
     * Validate data for filtering bookings.
     */
    public static function validateIndex(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'search' => 'nullable|string|max:255',
            'booking_status' => ['nullable', 'string', Rule::in(array_merge(BookingStatus::values(), ['all']))],
            'payment_status' => ['nullable', 'string', Rule::in(array_merge(PaymentStatus::values(), ['all']))],
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:created_at,booking_code,total_amount',
            'sort_order' => 'nullable|string|in:asc,desc',
        ], self::messages());
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'tour_id.required' => 'The tour ID is required.',
            'tour_id.integer' => 'The tour ID must be an integer.',
            'tour_id.exists' => 'The selected tour does not exist.',
            'tour_schedule_id.required' => 'The tour schedule ID is required.',
            'tour_schedule_id.integer' => 'The tour schedule ID must be an integer.',
            'tour_schedule_id.exists' => 'The selected tour schedule does not exist.',
            'quantity_adult.required' => 'The number of adults is required.',
            'quantity_adult.integer' => 'The number of adults must be an integer.',
            'quantity_adult.min' => 'The number of adults must be at least 1.',
            'quantity_child.integer' => 'The number of children must be an integer.',
            'quantity_child.min' => 'The number of children must be at least 0.',
            'quantity_infant.integer' => 'The number of infants must be an integer.',
            'quantity_infant.min' => 'The number of infants must be at least 0.',
            'customer_name.required' => 'The customer name is required.',
            'customer_name.string' => 'The customer name must be a string.',
            'customer_name.max' => 'The customer name may not be greater than :max characters.',
            'customer_email.required' => 'The customer email is required.',
            'customer_email.email' => 'The customer email must be a valid email address.',
            'customer_email.max' => 'The customer email may not be greater than :max characters.',
            'customer_phone.required' => 'The customer phone is required.',
            'customer_phone.string' => 'The customer phone must be a string.',
            'customer_phone.max' => 'The customer phone may not be greater than :max characters.',
            'customer_address.string' => 'The customer address must be a string.',
            'customer_address.max' => 'The customer address may not be greater than :max characters.',
            'customer_note.string' => 'The notes must be a string.',
            'payment_method.required' => 'The payment method is required.',
            'payment_method.string' => 'The payment method must be a string.',
            'payment_method.in' => 'The selected payment method is invalid.',
            'cancellation_reason.required' => 'The cancellation reason is required.',
            'cancellation_reason.string' => 'The cancellation reason must be a string.',
            'cancellation_reason.max' => 'The cancellation reason may not exceed :max characters.',
            'booking_status.required' => 'The booking status is required.',
            'booking_status.string' => 'The booking status must be a string.',
            'booking_status.in' => 'The selected booking status is invalid.',
            'payment_status.required' => 'The payment status is required.',
            'payment_status.string' => 'The payment status must be a string.',
            'payment_status.in' => 'The selected payment status is invalid.',
            'from_date.date_format' => 'The from date must be in Y-m-d format.',
            'to_date.date_format' => 'The to date must be in Y-m-d format.',
            'to_date.after_or_equal' => 'The to date must be a date after or equal to the from date.',
            'per_page.integer' => 'The per page value must be an integer.',
            'per_page.min' => 'The per page value must be at least 1.',
            'sort_by.string' => 'The sort by value must be a string.',
            'sort_by.in' => 'The selected sort by value is invalid.',
            'sort_order.string' => 'The sort order value must be a string.',
            'sort_order.in' => 'The selected sort order value is invalid.',
        ];
    }
}
