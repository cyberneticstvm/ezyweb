@php
    $logos = is_array($content['logos'] ?? null) ? $content['logos'] : [];
    $columns = in_array((string) ($content['logo_columns'] ?? ''), ['2', '3', '4', '5', '6'], true) ? (int) $content['logo_columns'] : 4;
    $size = in_array($content['logo_size'] ?? '', ['compact', 'large'], true) ? $content['logo_size'] : 'standard';
    $spacing = in_array($content['logo_spacing'] ?? '', ['compact', 'loose'], true) ? $content['logo_spacing'] : 'standard';
    $showNames = ($content['show_names'] ?? '0') === '1';
    $newTab = ($content['open_new_tab'] ?? '0') === '1';
@endphp
<div class="site-container site-logo-cloud variant-{{ $section->variant }} size-{{ $size }} spacing-{{ $spacing }} {{ ($content['show_cards'] ?? '0') === '1' ? 'has-cards' : '' }} {{ ($content['grayscale'] ?? '0') === '1' ? 'is-grayscale' : '' }}" style="--logo-columns: {{ $columns }};">
    <header class="site-logo-cloud-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($logos !== [])
        <ul class="site-logo-cloud-list" aria-label="{{ $content['heading'] ?: 'Client and partner logos' }}">
            @foreach ($logos as $logo)
                <li class="site-logo-cloud-item">
                    @if (!empty($logo['url']))
                        <a href="{{ $logo['url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $logo['name'] ?: ($logo['alt'] ?: 'Visit partner website') }}">
                    @endif
                        <img
                            src="{{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'medium'], absolute: false) }}"
                            srcset="{{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'medium'], absolute: false) }} 1280w"
                            sizes="(max-width: 767px) 45vw, 220px"
                            alt="{{ $logo['alt'] ?: ($logo['name'] ?? '') }}"
                            loading="lazy"
                        >
                        @if ($showNames && !empty($logo['name'])) <span class="site-logo-cloud-name">{{ $logo['name'] }}</span> @endif
                    @if (!empty($logo['url'])) </a> @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
