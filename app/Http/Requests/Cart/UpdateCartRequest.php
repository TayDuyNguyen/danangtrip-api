<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_adult' => 'required|integer|min:1',
            'quantity_child' => 'nullable|integer|min:0',
            'quantity_infant' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_adult.required' => 'The number of adults is required.',
            'quantity_adult.integer' => 'The number of adults must be an integer.',
            'quantity_adult.min' => 'The number of adults must be at least 1.',
            'quantity_child.integer' => 'The number of children must be an integer.',
            'quantity_child.min' => 'The number of children must be at least 0.',
            'quantity_infant.integer' => 'The number of infants must be an integer.',
            'quantity_infant.min' => 'The number of infants must be at least 0.',
        ];
    }
}
