<?php

namespace App\Themes;

use App\Models\Template;
use App\Models\Website;

class ThemeTokenService
{
    /** @return array<string, string> */
    public function forWebsite(Website $website, Template $template): array
    {
        /** @var array<string, string> $defaults */
        $defaults = $template->default_theme;
        /** @var array<string, string> $settings */
        $settings = $website->theme_settings;

        return array_replace($defaults, $settings);
    }

    /** @param array<string, string> $tokens
     * @return array<string, string>
     */
    public function cssVariables(array $tokens): array
    {
        return [
            '--theme-primary' => $tokens['primary'],
            '--theme-primary-foreground' => $tokens['primary_foreground'],
            '--theme-secondary' => $tokens['secondary'],
            '--theme-secondary-foreground' => $tokens['secondary_foreground'],
            '--theme-accent' => $tokens['accent'],
            '--theme-accent-foreground' => $tokens['accent_foreground'],
            '--theme-background' => $tokens['background'],
            '--theme-surface' => $tokens['surface'],
            '--theme-heading' => $tokens['heading'],
            '--theme-text' => $tokens['text'],
            '--theme-muted-text' => $tokens['muted_text'],
            '--theme-border' => $tokens['border'],
            '--theme-heading-font' => $tokens['heading_font'],
            '--theme-body-font' => $tokens['body_font'],
            '--theme-button-radius' => $tokens['button_radius'],
            '--theme-card-radius' => $tokens['card_radius'],
            '--theme-container-width' => $tokens['container_width'],
        ];
    }
}
