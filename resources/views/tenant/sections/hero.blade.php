@php
    $imageId = $content['image_id'] ?? '';
    $mobileImageId = $content['mobile_image_id'] ?? '';
    $hasImage = is_string($imageId) && $imageId !== '';
    $position = ($content['image_position'] ?? 'right') === 'left' ? 'image-left' : 'image-right';
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
@endphp
<div class="site-container site-hero-managed {{ $hasImage ? 'has-image '.$position : 'text-only' }}">
    <div class="site-copy site-hero-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h1>{{ $content['heading'] ?: $website->name }}</h1>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
        <div class="site-hero-actions">
            @if (!empty($content['primary_label']) && !empty($content['primary_url']))
                <a class="site-button" href="{{ $content['primary_url'] }}">{{ $content['primary_label'] }}</a>
            @endif
            @if (!empty($content['secondary_label']) && !empty($content['secondary_url']))
                <a class="site-button site-button-secondary" href="{{ $content['secondary_url'] }}">{{ $content['secondary_label'] }}</a>
            @endif
        </div>
    </div>
    @if ($hasImage)
        <picture class="site-hero-media fit-{{ $fit }}">
            @if ($mobileImageId)
                <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
            @endif
            <img
                src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}"
                srcset="{{ route('media.show', ['media' => $imageId, 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $imageId, 'variant' => 'medium'], absolute: false) }} 1280w, {{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }} 1920w"
                sizes="(max-width: 767px) 100vw, 50vw"
                alt="{{ $content['image_alt'] ?? '' }}"
                fetchpriority="high"
            >
        </picture>
    @endif
</div>
