<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

final class StoreLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', 'unique:landing_pages,slug'],
            'title' => ['required', 'string', 'max:150'],
            'page_type' => ['required', 'string', 'in:destination,tour_line,promotion'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'hero_image' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:150'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'filters' => ['nullable', 'array'],
            'content_blocks' => ['nullable', 'array'],
            'status' => ['required', 'string', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug must only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'Slug already exists.',
            'page_type.in' => 'Page type must be destination, tour_line, or promotion.',
            'status.in' => 'Status must be draft or published.',
        ];
    }
}
