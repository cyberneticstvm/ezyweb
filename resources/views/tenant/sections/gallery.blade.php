@php
    $selected = collect(preg_split('/\R/', (string) ($content['gallery_images'] ?? '')))->filter();
    $images = collect($items)
        ->when(($content['gallery_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['image'] ?? '')))
        ->when(!empty($content['gallery_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['gallery_category']));
    $images = match ($content['gallery_sort'] ?? 'display-order') {
        'title' => $images->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        'category' => $images->sortBy(fn ($item) => mb_strtolower((string) ($item['meta'] ?? ''))),
        default => $images,
    };
    $images = $images->take((int) ($content['gallery_limit'] ?? 12))->values();
    $lightbox = ($content['enable_lightbox'] ?? '1') === '1';
@endphp
<div
    class="site-container site-gallery-managed variant-{{ $section->variant }} gap-{{ $content['gallery_gap'] ?? 'normal' }}"
    style="--gallery-columns: {{ (int) ($content['gallery_columns'] ?? 3) }}"
    @if ($lightbox && $images->isNotEmpty()) data-gallery-lightbox @endif
>
    <header class="site-gallery-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($images->isNotEmpty())
        <div class="site-gallery-grid">
            @foreach ($images as $index => $image)
                @php
                    $ratio = ($content['respect_aspect_ratios'] ?? '1') === '1' ? ($image['aspect_ratio'] ?? 'auto') : 'square';
                    $showCopy = ($content['show_titles'] ?? '1') === '1' || ($content['show_captions'] ?? '1') === '1' || ($content['show_categories'] ?? '0') === '1';
                @endphp
                <figure class="site-gallery-item ratio-{{ $ratio }}">
                    @if ($lightbox)
                        <button
                            type="button"
                            class="site-gallery-trigger"
                            data-gallery-item
                            data-index="{{ $index }}"
                            data-image="{{ $image['image'] }}"
                            data-alt="{{ $image['alt'] }}"
                            data-title="{{ $image['title'] }}"
                            data-caption="{{ $image['description'] }}"
                            aria-label="Open {{ $image['title'] }} image"
                        >
                    @endif
                    <span class="site-gallery-media">
                        <img class="fit-{{ $content['image_fit'] ?? 'cover' }}" src="{{ $image['image'] }}" alt="{{ $image['alt'] }}" loading="lazy">
                        @if ($lightbox) <span class="site-gallery-zoom" aria-hidden="true">＋</span> @endif
                    </span>
                    @if ($lightbox) </button> @endif
                    @if ($showCopy)
                        <figcaption>
                            @if (($content['show_categories'] ?? '0') === '1' && !empty($image['meta'])) <p class="site-eyebrow">{{ $image['meta'] }}</p> @endif
                            @if (($content['show_titles'] ?? '1') === '1') <h3>{{ $image['title'] }}</h3> @endif
                            @if (($content['show_captions'] ?? '1') === '1' && !empty($image['description'])) <p>{{ $image['description'] }}</p> @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
        @if ($lightbox)
            <dialog class="site-gallery-dialog" data-gallery-dialog aria-label="Gallery image viewer">
                <button type="button" class="site-gallery-dialog-close" data-gallery-close aria-label="Close image viewer">×</button>
                @if ($images->count() > 1)
                    <button type="button" class="site-gallery-dialog-control previous" data-gallery-previous aria-label="Previous image">←</button>
                    <button type="button" class="site-gallery-dialog-control next" data-gallery-next aria-label="Next image">→</button>
                @endif
                <div class="site-gallery-dialog-body">
                    <img data-gallery-dialog-image src="" alt="">
                    @if (($content['lightbox_captions'] ?? '1') === '1')
                        <div class="site-gallery-dialog-copy">
                            <h3 data-gallery-dialog-title></h3>
                            <p data-gallery-dialog-caption></p>
                            <span data-gallery-dialog-status aria-live="polite"></span>
                        </div>
                    @endif
                </div>
            </dialog>
        @endif
    @endif
</div>
