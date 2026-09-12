<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:3000'],
            'address' => ['nullable', 'string', 'max:2000'], 'phone_numbers_text' => ['nullable', 'string', 'max:2000'],
            'email_addresses_text' => ['nullable', 'string', 'max:2000'], 'working_hours' => ['nullable', 'string', 'max:2000'],
            'maps_embed_url' => ['nullable', 'url:http,https', 'regex:/^https:\/\/(?:www\.)?google\.[a-z.]+\/maps\/embed/i', 'max:2048'], 'success_message' => ['required', 'string', 'max:500'],
            'recipient_email' => ['nullable', 'email:rfc', 'max:255'], 'whatsapp_number' => ['nullable', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'is_form_enabled' => ['required', 'boolean'], 'form_fields' => ['required', 'array'],
            'form_fields.name' => ['required', 'accepted'], 'form_fields.email' => ['required', 'boolean'],
            'form_fields.phone' => ['required', 'boolean'], 'form_fields.subject' => ['required', 'boolean'], 'form_fields.message' => ['required', 'accepted'],
        ];
    }
}
