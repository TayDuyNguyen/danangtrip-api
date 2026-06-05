<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

final class IndexLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page_type' => ['nullable', 'string', 'in:destination,tour_line,promotion'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:id,title,slug,page_type,status,created_at,updated_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
