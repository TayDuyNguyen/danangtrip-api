<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class MergeCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.tour_id' => 'required|integer|exists:tours,id',
            'items.*.tour_schedule_id' => 'required|integer|exists:tour_schedules,id',
            'items.*.quantity_adult' => 'required|integer|min:1',
            'items.*.quantity_child' => 'nullable|integer|min:0',
            'items.*.quantity_infant' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'The items array is required.',
            'items.array' => 'The items must be an array.',
            'items.*.tour_id.required' => 'Each item must have a tour ID.',
            'items.*.tour_id.exists' => 'The selected tour does not exist.',
            'items.*.tour_schedule_id.required' => 'Each item must have a tour schedule ID.',
            'items.*.tour_schedule_id.exists' => 'The selected tour schedule does not exist.',
            'items.*.quantity_adult.required' => 'Each item must specify number of adults.',
            'items.*.quantity_adult.min' => 'The number of adults in each item must be at least 1.',
        ];
    }
}
