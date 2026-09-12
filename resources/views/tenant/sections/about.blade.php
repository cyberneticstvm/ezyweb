@php
    $imageId = $content['image_id'] ?? '';
    $mobileImageId = $content['mobile_image_id'] ?? '';
    $hasImage = is_string($imageId) && $imageId !== '';
    $variantPosition = match ($section->variant) {
        'image-left' => 'left',
        'image-right' => 'right',
        default => $content['image_position'] ?? 'right',
    };
    $position = $variantPosition === 'left' ? 'image-left' : 'image-right';
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
@endphp
<div class="site-container site-about-managed {{ $hasImage ? 'has-image '.$position : 'text-only' }}">
    <div class="site-copy site-about-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['body'])) <div class="site-rich-text-body">{!! $content['body'] !!}</div> @endif
        @if (!empty($content['primary_label']) && !empty($content['primary_url']))
            <a class="site-button" href="{{ $content['primary_url'] }}">{{ $content['primary_label'] }}</a>
        @endif
    </div>
    @if ($hasImage)
        <figure class="site-about-media fit-{{ $fit }}">
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
            @if (!empty($content['image_caption'])) <figcaption>{{ $content['image_caption'] }}</figcaption> @endif
        </figure>
    @endif
</div>
