@php
    $testimonials = collect($items)
        ->when(($content['testimonial_featured_only'] ?? '0') === '1', fn ($collection) => $collection->filter(fn ($item) => ($item['featured'] ?? false) === true));
    $testimonials = match ($content['testimonial_sort'] ?? 'display-order') {
        'featured-first' => $testimonials->sortByDesc(fn ($item) => (bool) ($item['featured'] ?? false)),
        'rating-high' => $testimonials->sortByDesc(fn ($item) => (int) ($item['rating'] ?? 0)),
        default => $testimonials,
    };
    $testimonials = $testimonials->take((int) ($content['testimonial_limit'] ?? 6))->values();
    $isCarousel = in_array($section->variant, ['carousel', 'slider'], true) && $testimonials->count() > 1;
@endphp
<div
    class="site-container site-testimonials-managed variant-{{ $section->variant }}"
    style="--testimonial-per-view: {{ (int) ($content['slides_per_view'] ?? 1) }}"
    @if ($isCarousel)
        data-testimonial-carousel
        data-autoplay="{{ ($content['autoplay'] ?? '1') === '1' ? 'true' : 'false' }}"
        data-interval="{{ (int) ($content['interval'] ?? 6) * 1000 }}"
        data-pause="{{ ($content['pause_on_hover'] ?? '1') === '1' ? 'true' : 'false' }}"
    @endif
>
    <header class="site-testimonials-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($testimonials->isNotEmpty())
        <div class="site-testimonial-stage">
            <div class="site-testimonial-track" @if ($isCarousel) data-testimonial-track tabindex="0" aria-label="Client testimonials" @endif>
                @foreach ($testimonials as $index => $testimonial)
                    <article class="site-testimonial-card {{ ($testimonial['featured'] ?? false) ? 'is-featured' : '' }} {{ $section->variant === 'featured' && $index === 0 ? 'is-lead' : '' }}">
                        @if (($content['show_ratings'] ?? '1') === '1' && !empty($testimonial['rating']))
                            <p class="site-testimonial-rating" aria-label="{{ $testimonial['rating'] }} out of 5 stars"><span aria-hidden="true">{{ str_repeat('★', (int) $testimonial['rating']) }}</span></p>
                        @endif
                        <blockquote>“{{ $testimonial['description'] }}”</blockquote>
                        <footer class="site-testimonial-client">
                            @if (($content['show_photos'] ?? '1') === '1' && !empty($testimonial['image']))
                                <img src="{{ $testimonial['image'] }}" alt="" loading="lazy">
                            @endif
                            <div>
                                <cite>{{ $testimonial['title'] }}</cite>
                                @php
                                    $role = ($content['show_roles'] ?? '1') === '1' ? ($testimonial['client_role'] ?? '') : '';
                                    $company = ($content['show_companies'] ?? '1') === '1' ? ($testimonial['company'] ?? '') : '';
                                @endphp
                                @if ($role !== '' || $company !== '') <p>{{ implode(' · ', array_filter([$role, $company])) }}</p> @endif
                            </div>
                        </footer>
                    </article>
                @endforeach
            </div>
            @if ($isCarousel && ($content['show_arrows'] ?? '1') === '1')
                <button type="button" class="site-testimonial-control site-testimonial-previous" data-testimonial-previous aria-label="Previous testimonial">&#8592;</button>
                <button type="button" class="site-testimonial-control site-testimonial-next" data-testimonial-next aria-label="Next testimonial">&#8594;</button>
            @endif
        </div>
        @if ($isCarousel && ($content['show_dots'] ?? '1') === '1')
            <div class="site-testimonial-dots" role="tablist" aria-label="Choose testimonial">
                @foreach ($testimonials as $index => $testimonial)
                    <button type="button" data-testimonial-dot data-index="{{ $index }}" aria-label="Show testimonial {{ $index + 1 }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
        @if ($isCarousel) <p class="site-sr-only" data-testimonial-status aria-live="polite"></p> @endif
    @endif
</div>
