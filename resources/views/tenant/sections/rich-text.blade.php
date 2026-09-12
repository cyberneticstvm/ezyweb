@php
    $imageId = $content['image_id'] ?? '';
    $hasImage = is_string($imageId) && $imageId !== '';
    $position = ($content['image_position'] ?? 'right') === 'left' ? 'image-left' : 'image-right';
@endphp
<div class="site-container site-rich-text-wrap variant-{{ $section->variant }}">
    <div class="site-rich-text-grid {{ $hasImage ? 'has-image '.$position : 'text-only' }}">
        <div class="site-copy site-rich-text-copy">
            @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
            @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
            @if (!empty($content['body']))
                <div class="site-rich-text-body">{!! $content['body'] !!}</div>
            @endif
        </div>
        @if ($hasImage)
            <figure class="site-rich-text-media">
                <img
                    src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}"
                    srcset="{{ route('media.show', ['media' => $imageId, 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $imageId, 'variant' => 'medium'], absolute: false) }} 1280w, {{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }} 1920w"
                    sizes="(max-width: 767px) 100vw, 50vw"
                    alt="{{ $content['image_alt'] ?? '' }}"
                    loading="lazy"
                >
                @if (!empty($content['image_caption'])) <figcaption>{{ $content['image_caption'] }}</figcaption> @endif
            </figure>
        @endif
    </div>
</div>
