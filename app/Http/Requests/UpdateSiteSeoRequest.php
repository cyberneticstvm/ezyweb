<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSiteSeoRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'site_title' => ['required', 'string', 'max:70'], 'default_meta_description' => ['nullable', 'string', 'max:320'],
            'default_social_image' => ['nullable', 'url:http,https', 'max:2048'], 'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::in(['Organization', 'LocalBusiness'])], 'primary_language' => ['required', 'regex:/^[a-z]{2,3}(?:-[A-Z]{2})?$/'],
            'search_engine_visibility' => ['required', 'boolean'], 'google_site_verification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'google_analytics_id' => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/'], 'social_profiles_text' => ['nullable', 'string', 'max:5000'],
            'not_found_title' => ['required', 'string', 'max:120'], 'not_found_message' => ['nullable', 'string', 'max:500'],
            'not_found_button_label' => ['required', 'string', 'max:60'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $urls = preg_split('/\R/', (string) $this->input('social_profiles_text')) ?: [];
            foreach (array_filter(array_map('trim', $urls)) as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL) === false || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                    $validator->errors()->add('social_profiles_text', 'Each social profile must be a complete HTTPS URL.');

                    break;
                }
            }
        });
    }
}
