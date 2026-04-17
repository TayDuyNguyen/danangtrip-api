<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('booking_status') && $this->filled('status')) {
            $merge['booking_status'] = $this->input('status');
        }

        if (! $this->filled('sort_by') && $this->filled('sort')) {
            $merge['sort_by'] = $this->input('sort');
        }

        if (! $this->filled('sort_order') && $this->filled('order')) {
            $merge['sort_order'] = $this->input('order');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'booking_status' => ['nullable', 'string', Rule::in(array_merge(BookingStatus::values(), ['all']))],
            'payment_status' => ['nullable', 'string', Rule::in(array_merge(PaymentStatus::values(), ['all']))],
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:id,created_at,booked_at,booking_code,total_amount,booking_status,payment_status',
            'sort_order' => 'nullable|string|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'booking_status.in' => 'The selected booking status is invalid.',
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
