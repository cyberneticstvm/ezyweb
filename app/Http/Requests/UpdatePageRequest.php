<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s_-]+$/'],
            'navigation_label' => ['nullable', 'string', 'max:80'],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
