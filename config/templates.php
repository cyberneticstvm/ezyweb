<?php

$baseTheme = [
    'primary_foreground' => '#ffffff',
    'secondary_foreground' => '#ffffff',
    'accent_foreground' => '#111827',
    'heading_font' => 'Inter',
    'body_font' => 'Inter',
    'button_style' => 'solid',
    'button_radius' => '0.5rem',
    'card_radius' => '0.75rem',
    'shadow_strength' => 'subtle',
    'container_width' => '1200px',
    'section_spacing' => 'comfortable',
];

return [
    'catalog' => [
        'horizon' => [
            'name' => 'Horizon',
            'description' => 'A modern corporate system with strong geometry, generous whitespace, and crisp calls to action.',
            'suitable_categories' => ['agency', 'consulting', 'software', 'corporate'],
            'preview_desktop_path' => '/images/templates/horizon-desktop.svg',
            'preview_mobile_path' => '/images/templates/horizon-mobile.svg',
            'default_theme' => [...$baseTheme, 'primary' => '#0f172a', 'secondary' => '#475569', 'accent' => '#2563eb', 'background' => '#ffffff', 'surface' => '#f8fafc', 'heading' => '#0f172a', 'text' => '#334155', 'muted_text' => '#64748b', 'border' => '#e2e8f0'],
            'variant_map' => ['hero' => 'split', 'about' => 'image-left', 'services' => 'cards', 'testimonials' => 'grid', 'contact' => 'split'],
        ],
        'localpro' => [
            'name' => 'LocalPro',
            'description' => 'A warm, conversion-focused system that keeps contact actions and local credibility highly visible.',
            'suitable_categories' => ['clinic', 'education', 'restaurant', 'retail', 'local_service'],
            'preview_desktop_path' => '/images/templates/localpro-desktop.svg',
            'preview_mobile_path' => '/images/templates/localpro-mobile.svg',
            'default_theme' => [...$baseTheme, 'primary' => '#047857', 'secondary' => '#57534e', 'accent' => '#f59e0b', 'background' => '#fffbeb', 'surface' => '#ffffff', 'heading' => '#1c1917', 'text' => '#44403c', 'muted_text' => '#78716c', 'border' => '#e7e5e4', 'button_radius' => '9999px', 'card_radius' => '1rem'],
            'variant_map' => ['hero' => 'contact-first', 'about' => 'image-right', 'services' => 'icon-list', 'testimonials' => 'featured', 'contact' => 'map-hours'],
        ],
        'canvas' => [
            'name' => 'Canvas',
            'description' => 'An editorial, image-led system with elegant typography and portfolio-focused compositions.',
            'suitable_categories' => ['creative', 'architecture', 'photography', 'events'],
            'preview_desktop_path' => '/images/templates/canvas-desktop.svg',
            'preview_mobile_path' => '/images/templates/canvas-mobile.svg',
            'default_theme' => [...$baseTheme, 'primary' => '#27272a', 'secondary' => '#52525b', 'accent' => '#7c3aed', 'background' => '#fafaf9', 'surface' => '#ffffff', 'heading' => '#18181b', 'text' => '#3f3f46', 'muted_text' => '#71717a', 'border' => '#e7e5e4', 'heading_font' => 'Playfair Display', 'card_radius' => '0.25rem', 'container_width' => '1280px', 'section_spacing' => 'spacious'],
            'variant_map' => ['hero' => 'editorial', 'about' => 'centered', 'services' => 'alternating', 'portfolio' => 'masonry', 'testimonials' => 'quote'],
        ],
    ],
    'presets' => [
        'ocean' => ['name' => 'Ocean', 'primary' => '#0f766e', 'secondary' => '#334155', 'accent' => '#f59e0b', 'background' => '#ffffff', 'surface' => '#f8fafc', 'heading' => '#0f172a', 'text' => '#334155', 'muted_text' => '#64748b', 'border' => '#e2e8f0'],
        'indigo' => ['name' => 'Indigo', 'primary' => '#4338ca', 'secondary' => '#475569', 'accent' => '#ec4899', 'background' => '#ffffff', 'surface' => '#f8fafc', 'heading' => '#111827', 'text' => '#374151', 'muted_text' => '#6b7280', 'border' => '#e5e7eb'],
        'forest' => ['name' => 'Forest', 'primary' => '#166534', 'secondary' => '#3f3f46', 'accent' => '#d97706', 'background' => '#fefce8', 'surface' => '#ffffff', 'heading' => '#1c1917', 'text' => '#44403c', 'muted_text' => '#78716c', 'border' => '#e7e5e4'],
        'rose' => ['name' => 'Rose', 'primary' => '#9f1239', 'secondary' => '#4c0519', 'accent' => '#f59e0b', 'background' => '#fff7ed', 'surface' => '#ffffff', 'heading' => '#4c0519', 'text' => '#57534e', 'muted_text' => '#78716c', 'border' => '#fed7aa'],
        'mono' => ['name' => 'Monochrome', 'primary' => '#18181b', 'secondary' => '#52525b', 'accent' => '#7c3aed', 'background' => '#fafafa', 'surface' => '#ffffff', 'heading' => '#09090b', 'text' => '#3f3f46', 'muted_text' => '#71717a', 'border' => '#e4e4e7'],
    ],
];
