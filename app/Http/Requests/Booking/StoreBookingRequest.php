<?php

namespace App\Http\Requests\Booking;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
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
        ];
    }
}
