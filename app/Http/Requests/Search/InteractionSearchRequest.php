<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InteractionSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::in(['suggestion_click', 'trending_click', 'result_click'])],
            'query' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(['location', 'tour', 'all'])],
            'clicked_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'clicked_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'clicked_type' => ['sometimes', 'nullable', 'string', Rule::in(['location', 'tour', 'keyword'])],
            'source' => ['sometimes', 'nullable', 'string', 'max:50'],
            'session_id' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
