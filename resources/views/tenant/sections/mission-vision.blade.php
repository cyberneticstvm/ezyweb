@php
    $areas = ['story', 'mission', 'vision'];
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $storyPosition = ($content['image_position'] ?? 'left') === 'right' ? 'image-right' : 'image-left';
@endphp
<div class="site-container site-purpose-managed variant-{{ $section->variant }} fit-{{ $fit }} {{ $storyPosition }}">
    @if (!empty($content['eyebrow']) || !empty($content['heading']))
        <header class="site-purpose-header">
            @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
            @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        </header>
    @endif
    <div class="site-purpose-grid">
        @foreach ($areas as $area)
            @php
                $imageId = $content[$area.'_image_id'] ?? '';
                $mobileImageId = $content[$area.'_mobile_image_id'] ?? '';
                $body = $content[$area.'_body'] ?? ($area === 'story' ? ($content['description'] ?? '') : '');
                $hasImage = is_string($imageId) && $imageId !== '';
            @endphp
            <article class="site-purpose-item site-purpose-{{ $area }} {{ $hasImage ? 'has-image' : 'text-only' }}">
                @if ($hasImage)
                    <figure class="site-purpose-media">
                        <picture>
                            @if ($mobileImageId)
                                <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
                            @endif
                            <img
                                src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}"
                                srcset="{{ route('media.show', ['media' => $imageId, 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $imageId, 'variant' => 'medium'], absolute: false) }} 1280w, {{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }} 1920w"
                                sizes="(max-width: 767px) 100vw, {{ $section->variant === 'side-by-side' || $section->variant === 'cards' ? '33vw' : '50vw' }}"
                                alt="{{ $content[$area.'_image_alt'] ?? '' }}"
                                loading="lazy"
                            >
                        </picture>
                        @if (!empty($content[$area.'_image_caption'])) <figcaption>{{ $content[$area.'_image_caption'] }}</figcaption> @endif
                    </figure>
                @endif
                <div class="site-purpose-copy">
                    @if (!empty($content[$area.'_heading'])) <h3>{{ $content[$area.'_heading'] }}</h3> @endif
                    @if (!empty($body)) <div class="site-rich-text-body">{!! $body !!}</div> @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
