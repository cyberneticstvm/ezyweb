<?php

namespace App\Http\Requests;

use App\Data\OnboardingData;
use App\Enums\WebsiteType;
use App\Rules\AvailableSubdomain;
use App\Tenancy\SubdomainService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:160'],
            'business_category' => ['required', Rule::in(array_keys(config('onboarding.categories')))],
            'country_code' => ['required', Rule::in(array_keys(config('onboarding.countries')))],
            'timezone' => ['required', Rule::in(array_keys(config('onboarding.timezones')))],
            'default_language' => ['required', Rule::in(array_keys(config('onboarding.languages')))],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'subdomain' => ['required', app(AvailableSubdomain::class)],
            'website_type' => ['required', Rule::enum(WebsiteType::class)],
            'template_key' => ['required', Rule::in(array_keys(config('onboarding.templates')))],
            'theme' => ['required', 'array'],
            'theme.palette' => ['required', Rule::in(array_keys(config('onboarding.palettes')))],
            'theme.primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.heading_font' => ['required', Rule::in(['Inter', 'Manrope', 'Playfair Display'])],
            'theme.body_font' => ['required', Rule::in(['Inter', 'Source Sans 3', 'Lato'])],
            'theme.button_radius' => ['required', Rule::in(['0.25rem', '0.5rem', '0.75rem', '9999px'])],
            'theme.corner_radius' => ['required', Rule::in(['0.25rem', '0.5rem', '0.75rem', '1rem'])],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'distinct', Rule::in(array_keys(config('onboarding.sections')))],
            'confirmed' => ['accepted'],
        ];
    }

    public function onboardingData(): OnboardingData
    {
        /** @var array<string, string> $theme */
        $theme = $this->validated('theme');
        /** @var array<int, string> $sections */
        $sections = $this->validated('sections');

        return new OnboardingData(
            businessName: $this->string('business_name')->toString(),
            businessCategory: $this->string('business_category')->toString(),
            countryCode: $this->string('country_code')->toString(),
            timezone: $this->string('timezone')->toString(),
            defaultLanguage: $this->string('default_language')->toString(),
            contactEmail: $this->string('contact_email')->toString(),
            contactPhone: $this->filled('contact_phone') ? $this->string('contact_phone')->toString() : null,
            subdomain: $this->string('subdomain')->toString(),
            websiteType: $this->string('website_type')->toString(),
            templateKey: $this->string('template_key')->toString(),
            theme: $theme,
            sections: $sections,
        );
    }

    protected function prepareForValidation(): void
    {
        $subdomains = app(SubdomainService::class);
        $this->merge([
            'subdomain' => $subdomains->normalize((string) $this->input('subdomain')),
            'country_code' => strtoupper((string) $this->input('country_code')),
            'default_language' => strtolower((string) $this->input('default_language')),
        ]);
    }
}
