<?php

namespace App\Http\Requests;

use App\Contact\PublicContactConfig;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $website = app(CurrentTenant::class)->website();
        $settings = $website === null ? [] : app(PublicContactConfig::class)->forWebsite($website);
        $fields = is_array($settings['form_fields'] ?? null) ? $settings['form_fields'] : [];
        $emailEnabled = ($fields['email'] ?? true) === true;
        $phoneEnabled = ($fields['phone'] ?? true) === true;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:255', ...($emailEnabled && $phoneEnabled ? ['required_without:phone'] : ($emailEnabled ? ['required'] : []))],
            'phone' => ['nullable', 'string', 'max:40', ...($emailEnabled && $phoneEnabled ? ['required_without:email'] : ($phoneEnabled ? ['required'] : []))], 'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'], 'company_website' => ['nullable', 'max:0'],
            'cf-turnstile-response' => [(string) config('services.turnstile.secret_key') !== '' ? 'required' : 'nullable', 'string', 'max:2048'],
        ];
    }
}
