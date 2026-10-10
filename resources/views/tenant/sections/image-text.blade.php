@php
    $imageId = $content['image_id'] ?? '';
    $mobileImageId = $content['mobile_image_id'] ?? '';
    $hasImage = is_string($imageId) && $imageId !== '';
    $position = match ($section->variant) {
        'image-left' => 'left',
        'image-right' => 'right',
        default => ($content['image_position'] ?? 'right') === 'left' ? 'left' : 'right',
    };
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $ratio = in_array($content['image_ratio'] ?? '', ['square', 'portrait'], true) ? $content['image_ratio'] : 'landscape';
    $size = in_array($content['image_size'] ?? '', ['compact', 'large'], true) ? $content['image_size'] : 'balanced';
@endphp
<div class="site-container site-image-text-managed variant-{{ $section->variant }} {{ $hasImage ? 'has-image image-'.$position.' image-size-'.$size : 'text-only' }}">
    <div class="site-image-text-copy site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
        @if (!empty($content['body'])) <div class="site-rich-text-body">{!! $content['body'] !!}</div> @endif
        @if ((!empty($content['primary_label']) && !empty($content['primary_url'])) || (!empty($content['secondary_label']) && !empty($content['secondary_url'])))
            <div class="site-image-text-actions">
                @if (!empty($content['primary_label']) && !empty($content['primary_url']))
                    <a class="site-button site-button-accent" href="{{ $content['primary_url'] }}" @if (($content['primary_new_tab'] ?? '0') === '1') target="_blank" rel="noopener noreferrer" @endif>{{ $content['primary_label'] }}</a>
                @endif
                @if (!empty($content['secondary_label']) && !empty($content['secondary_url']))
                    <a class="site-button site-button-secondary" href="{{ $content['secondary_url'] }}" @if (($content['secondary_new_tab'] ?? '0') === '1') target="_blank" rel="noopener noreferrer" @endif>{{ $content['secondary_label'] }}</a>
                @endif
            </div>
        @endif
        @if (!empty($content['supporting_text'])) <p class="site-image-text-supporting">{{ $content['supporting_text'] }}</p> @endif
    </div>
    @if ($hasImage)
        <figure class="site-image-text-media fit-{{ $fit }} ratio-{{ $ratio }}">
            <picture>
                @if ($mobileImageId)
                    <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
                @endif
                <img
                    src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}"
                    srcset="{{ route('media.show', ['media' => $imageId, 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $imageId, 'variant' => 'medium'], absolute: false) }} 1280w, {{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }} 1920w"
                    sizes="(max-width: 767px) 100vw, 50vw"
                    alt="{{ $content['image_alt'] ?? '' }}"
                    loading="lazy"
                >
            </picture>
            @if (($content['show_image_caption'] ?? '1') === '1' && !empty($content['image_caption'])) <figcaption>{{ $content['image_caption'] }}</figcaption> @endif
        </figure>
    @endif
</div>
