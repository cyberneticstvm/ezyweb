<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:80'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'registrations_enabled' => ['required', 'boolean'],
            'default_country_code' => ['required', Rule::in(array_keys(config('onboarding.countries')))],
            'default_timezone' => ['required', Rule::in(array_keys(config('onboarding.timezones')))],
            'default_locale' => ['required', Rule::in(array_keys(config('onboarding.languages')))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_country_code' => strtoupper((string) $this->input('default_country_code')),
            'default_locale' => strtolower((string) $this->input('default_locale')),
        ]);
    }
}
