<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWhatsappSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'], 'phone_number' => ['nullable', 'required_if:is_enabled,true', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'default_message' => ['required', 'string', 'max:500'], 'show_on_desktop' => ['required', 'boolean'],
            'show_on_tablet' => ['required', 'boolean'], 'show_on_mobile' => ['required', 'boolean'],
            'position' => ['required', Rule::in(['left', 'right'])], 'bottom_offset' => ['required', 'integer', 'between:0,240'],
            'display_delay' => ['required', 'integer', 'between:0,30'], 'pulse_animation' => ['required', 'boolean'],
            'show_label' => ['required', 'boolean'], 'included_pages_text' => ['nullable', 'string', 'max:5000'],
            'mobile_sticky_bar' => ['required', 'boolean'], 'call_number' => ['nullable', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'directions_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
