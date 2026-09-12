<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubdomainAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['subdomain' => ['required', 'string', 'max:40']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subdomain' => strtolower(trim((string) $this->input('subdomain'))),
        ]);
    }
}
