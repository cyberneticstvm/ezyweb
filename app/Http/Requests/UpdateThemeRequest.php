<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $colors = ['primary', 'primary_foreground', 'secondary', 'secondary_foreground', 'accent', 'accent_foreground', 'background', 'surface', 'heading', 'text', 'muted_text', 'border'];
        $rules = [
            'theme' => ['required', 'array:'.implode(',', [...$colors, 'heading_font', 'body_font', 'button_style', 'button_radius', 'card_radius', 'shadow_strength', 'container_width', 'section_spacing'])],
            'theme.heading_font' => ['required', Rule::in(['Inter', 'Manrope', 'Playfair Display'])],
            'theme.body_font' => ['required', Rule::in(['Inter', 'Source Sans 3', 'Lato'])],
            'theme.button_style' => ['required', Rule::in(['solid', 'outline', 'soft'])],
            'theme.button_radius' => ['required', Rule::in(['0.25rem', '0.5rem', '0.75rem', '9999px'])],
            'theme.card_radius' => ['required', Rule::in(['0.25rem', '0.5rem', '0.75rem', '1rem'])],
            'theme.shadow_strength' => ['required', Rule::in(['none', 'subtle', 'medium'])],
            'theme.container_width' => ['required', Rule::in(['1080px', '1200px', '1280px'])],
            'theme.section_spacing' => ['required', Rule::in(['compact', 'comfortable', 'spacious'])],
        ];

        foreach ($colors as $color) {
            $rules["theme.{$color}"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function theme(): array
    {
        /** @var array<string, string> $theme */
        $theme = $this->validated('theme');

        return array_map(static fn (string $value): string => str_starts_with($value, '#') ? strtolower($value) : $value, $theme);
    }
}
