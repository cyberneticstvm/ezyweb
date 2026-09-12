@php
    $logos = is_array($content['logos'] ?? null) ? $content['logos'] : [];
    $isCarousel = $section->variant === 'carousel' && count($logos) > 1;
    $showNames = ($content['show_names'] ?? '0') === '1';
    $newTab = ($content['open_new_tab'] ?? '0') === '1';
@endphp
<div
    class="site-container site-brands-managed variant-{{ $section->variant }} {{ ($content['grayscale'] ?? '0') === '1' ? 'is-grayscale' : '' }}"
    @if ($isCarousel)
        data-brand-carousel
        data-autoplay="{{ ($content['autoplay'] ?? '1') === '1' ? 'true' : 'false' }}"
        data-interval="{{ (int) ($content['interval'] ?? 4) * 1000 }}"
        data-pause="{{ ($content['pause_on_hover'] ?? '1') === '1' ? 'true' : 'false' }}"
    @endif
>
    <header class="site-brands-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($logos !== [])
        <div class="site-brand-stage">
            <div class="site-brand-track" @if ($isCarousel) data-brand-track tabindex="0" aria-label="Brand and partner logos" @endif>
                @foreach ($logos as $logo)
                    <article class="site-brand-item">
                        @if (!empty($logo['url']))
                            <a href="{{ $logo['url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $logo['name'] ?: ($logo['alt'] ?: 'Partner website') }}">
                        @endif
                        <img
                            src="{{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'medium'], absolute: false) }}"
                            srcset="{{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'small'], absolute: false) }} 640w, {{ route('media.show', ['media' => $logo['image_id'], 'variant' => 'medium'], absolute: false) }} 1280w"
                            sizes="(max-width: 767px) 45vw, 220px"
                            alt="{{ $logo['alt'] ?? '' }}"
                            loading="lazy"
                        >
                        @if (!empty($logo['url'])) </a> @endif
                        @if ($showNames && !empty($logo['name'])) <p>{{ $logo['name'] }}</p> @endif
                    </article>
                @endforeach
            </div>
            @if ($isCarousel && ($content['show_arrows'] ?? '1') === '1')
                <button type="button" class="site-brand-control site-brand-previous" data-brand-previous aria-label="Previous partner logos">&#8592;</button>
                <button type="button" class="site-brand-control site-brand-next" data-brand-next aria-label="Next partner logos">&#8594;</button>
            @endif
        </div>
        @if ($isCarousel) <p class="site-sr-only" data-brand-status aria-live="polite"></p> @endif
    @endif
</div>
