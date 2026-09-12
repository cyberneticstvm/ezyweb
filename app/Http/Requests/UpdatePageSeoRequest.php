<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageSeoRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'seo_title' => ['nullable', 'string', 'max:70'], 'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url:https', 'max:2048'], 'open_graph_title' => ['nullable', 'string', 'max:100'],
            'open_graph_description' => ['nullable', 'string', 'max:320'], 'open_graph_image' => ['nullable', 'url:http,https', 'max:2048'],
            'is_indexed' => ['required', 'boolean'], 'is_followed' => ['required', 'boolean'],
            'schema_type' => ['required', Rule::in(['WebPage', 'AboutPage', 'ContactPage'])],
        ];
    }
}
