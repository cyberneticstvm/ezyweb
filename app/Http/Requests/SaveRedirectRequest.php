<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRedirectRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[A-Za-z0-9\/_\-.~%]*$/'],
            'target_path' => ['required', 'string', 'max:500', 'regex:/^\/(?!\/)[A-Za-z0-9\/_\-.~%?=&+]*$/'],
            'status_code' => ['required', 'integer', Rule::in([301, 302])], 'is_active' => ['required', 'boolean'],
        ];
    }
}
