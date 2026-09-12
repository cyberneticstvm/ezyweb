<!DOCTYPE html>
<html lang="{{ $seo['language'] ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('tenant.partials.seo-head')
    <title>{{ $seo['title'] ?? ($page->title.' · '.$website->name) }}</title>
    @vite(['resources/css/app.css', 'resources/js/tenant-site.ts'])
</head>
<body class="tenant-site template-{{ $template->key }} button-style-{{ $theme['button_style'] }} shadow-{{ $theme['shadow_strength'] }} site-density-{{ $theme['section_spacing'] }}" style="{{ $themeStyle }}">
    @if ($previewMode)
        <aside class="site-preview-banner" role="status">
            <strong>{{ $previewLabel ?? 'Preview' }}</strong>
            <span>This private preview may contain unpublished changes and expires automatically.</span>
        </aside>
    @endif
    <a href="#main-content" class="site-skip-link">Skip to content</a>
    <div id="top"></div>
    @include('tenant.partials.header')

    <main id="main-content" tabindex="-1">
        @foreach ($sections as $section)
            @php
                $design = $section->design_settings;
                $visibility = implode(' ', array_filter([
                    $section->show_on_desktop ? null : 'site-hide-desktop',
                    $section->show_on_tablet ? null : 'site-hide-tablet',
                    $section->show_on_mobile ? null : 'site-hide-mobile',
                ]));
            @endphp
            <section
                @if ($section->anchor) id="{{ $section->anchor }}" @endif
                class="site-section section-{{ $section->type }} variant-{{ $section->variant }} surface-{{ $design['surface'] }} spacing-{{ $design['spacing'] }} align-{{ $design['alignment'] }} {{ $visibility }}"
                data-section="{{ $section->type }}"
            >
                @include($sectionViews[$section->type] ?? 'tenant.sections.content', [
                    'content' => $section->content,
                    'section' => $section,
                    'items' => $contentCollections[$section->type] ?? [],
                    'contactSettings' => $siteSettings['contact'] ?? [],
                ])
            </section>
        @endforeach
    </main>

    @include('tenant.partials.footer')
    @include('tenant.partials.whatsapp', ['settings' => $siteSettings['whatsapp'] ?? []])
    @if (!empty($turnstileSiteKey) && !empty($siteSettings['contact']['is_form_enabled']) && !$previewMode)
        <script nonce="{{ Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</body>
</html>
