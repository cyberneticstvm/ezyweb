@php
    $slides = is_array($content['slides'] ?? null) ? $content['slides'] : [];
    $multiple = count($slides) > 1;
    $showArrows = $multiple && ($content['show_arrows'] ?? '1') === '1';
    $showDots = $multiple && ($content['show_dots'] ?? '1') === '1';
@endphp
<div
    class="site-hero-slider"
    data-hero-slider
    data-autoplay="{{ ($content['autoplay'] ?? '1') === '1' && $multiple ? 'true' : 'false' }}"
    data-interval="{{ (int) ($content['interval'] ?? 5) * 1000 }}"
    aria-roledescription="carousel"
    aria-label="Featured content"
>
    <div class="site-hero-slides" aria-live="off">
        @foreach ($slides as $index => $slide)
            @php
                $imageId = $slide['image_id'] ?? '';
                $mobileImageId = $slide['mobile_image_id'] ?? '';
            @endphp
            <article
                class="site-hero-slide"
                data-hero-slide
                aria-roledescription="slide"
                aria-label="{{ $index + 1 }} of {{ count($slides) }}"
                @if ($index > 0) hidden @endif
            >
                @if ($imageId)
                    <picture class="site-hero-slide-picture">
                        @if ($mobileImageId)
                            <source media="(max-width: 767px)" srcset="{{ route('media.show', ['media' => $mobileImageId, 'variant' => 'medium'], absolute: false) }}">
                        @endif
                        <img
                            src="{{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }}"
                            srcset="{{ route('media.show', ['media' => $imageId, 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $imageId, 'variant' => 'medium'], absolute: false) }} 1280w, {{ route('media.show', ['media' => $imageId, 'variant' => 'large'], absolute: false) }} 1920w"
                            sizes="100vw"
                            alt="{{ $slide['alt'] ?? '' }}"
                            width="1920"
                            height="1080"
                            @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                        >
                    </picture>
                @endif
                <div class="site-hero-slide-shade"></div>
                <div class="site-container site-hero-slide-content">
                    <div class="site-copy">
                        @if (!empty($slide['eyebrow'])) <p class="site-eyebrow">{{ $slide['eyebrow'] }}</p> @endif
                        <h1>{{ $slide['heading'] ?: $website->name }}</h1>
                        @if (!empty($slide['description'])) <p class="site-lead">{{ $slide['description'] }}</p> @endif
                        @if (!empty($slide['primary_label']) && !empty($slide['primary_url']))
                            <a class="site-button" href="{{ $slide['primary_url'] }}">{{ $slide['primary_label'] }}</a>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
    @if ($showArrows)
        <button type="button" class="site-hero-control site-hero-previous" data-hero-previous aria-label="Previous slide">&#8592;</button>
        <button type="button" class="site-hero-control site-hero-next" data-hero-next aria-label="Next slide">&#8594;</button>
    @endif
    @if ($showDots)
        <div class="site-hero-dots" role="tablist" aria-label="Choose slide">
            @foreach ($slides as $index => $slide)
                <button type="button" data-hero-dot data-index="{{ $index }}" role="tab" aria-label="Show slide {{ $index + 1 }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    @endif
    <p class="site-sr-only" data-hero-status aria-live="polite"></p>
</div>
