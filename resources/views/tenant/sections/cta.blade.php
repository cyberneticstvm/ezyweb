@php
    $imageId = $content['image_id'] ?? '';
    $mobileImageId = $content['mobile_image_id'] ?? '';
    $hasImage = is_string($imageId) && $imageId !== '';
    $layout = in_array($content['media_layout'] ?? null, ['left', 'right', 'background'], true) ? $content['media_layout'] : 'right';
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $background = $hasImage && $layout === 'background';
    $primaryNewTab = ($content['primary_new_tab'] ?? '0') === '1';
    $secondaryNewTab = ($content['secondary_new_tab'] ?? '0') === '1';
@endphp
<div class="site-container site-cta-managed variant-{{ $section->variant }} {{ $hasImage ? 'has-image image-'.$layout : 'text-only' }} {{ $background ? 'has-background' : '' }}">
    @if ($background)
        <picture class="site-cta-background fit-{{ $fit }}">
            @if ($mobileImageId)
                <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
            @endif
            <img src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}" alt="{{ $content['image_alt'] ?? '' }}" loading="lazy">
        </picture>
        <span class="site-cta-overlay" style="--cta-overlay: {{ (int) ($content['overlay_strength'] ?? 55) / 100 }}"></span>
    @endif
    <div class="site-copy site-cta-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: 'Take the next step' }}</h2>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
        <div class="site-cta-actions">
            @if (!empty($content['primary_label']) && !empty($content['primary_url']))
                <a class="site-button site-button-accent" href="{{ $content['primary_url'] }}" @if ($primaryNewTab) target="_blank" rel="noopener noreferrer" @endif>{{ $content['primary_label'] }}</a>
            @endif
            @if (!empty($content['secondary_label']) && !empty($content['secondary_url']))
                <a class="site-button site-button-secondary" href="{{ $content['secondary_url'] }}" @if ($secondaryNewTab) target="_blank" rel="noopener noreferrer" @endif>{{ $content['secondary_label'] }}</a>
            @endif
        </div>
        @if (!empty($content['supporting_text'])) <p class="site-cta-supporting">{{ $content['supporting_text'] }}</p> @endif
    </div>
    @if ($hasImage && !$background)
        <picture class="site-cta-media fit-{{ $fit }}">
            @if ($mobileImageId)
                <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
            @endif
            <img src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}" alt="{{ $content['image_alt'] ?? '' }}" loading="lazy">
        </picture>
    @endif
</div>
