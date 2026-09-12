@php
    $selectedSlugs = collect(explode(',', $content['product_slugs'] ?? ''))->filter();
    $catalogue = collect($items)
        ->when(($content['selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selectedSlugs->contains($item['slug'] ?? '')))
        ->when(($content['featured_only'] ?? '0') === '1', fn ($collection) => $collection->filter(fn ($item) => ($item['featured'] ?? false) === true));
    $catalogue = match ($content['sort'] ?? 'display-order') {
        'featured-first' => $catalogue->sortByDesc(fn ($item) => (bool) ($item['featured'] ?? false)),
        'name' => $catalogue->sortBy(fn ($item) => mb_strtolower($item['title'] ?? '')),
        'price-low' => $catalogue->sortBy(fn ($item) => (float) ($item['price'] ?? 0)),
        'price-high' => $catalogue->sortByDesc(fn ($item) => (float) ($item['price'] ?? 0)),
        default => $catalogue,
    };
    $catalogue = $catalogue->take((int) ($content['limit'] ?? 6))->values();
    $showImages = ($content['show_images'] ?? '1') === '1';
    $showDescriptions = ($content['show_descriptions'] ?? '1') === '1';
    $showPrices = ($content['show_prices'] ?? '1') === '1';
    $showButton = ($content['show_button'] ?? '1') === '1' && !empty($content['button_url']);
    $imageFit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
@endphp
<div class="site-container site-products-managed variant-{{ $section->variant }} fit-{{ $imageFit }}">
    <header class="site-products-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($catalogue->isNotEmpty())
        <div class="site-product-grid" style="--product-columns: {{ (int) ($content['columns'] ?? 3) }}">
            @foreach ($catalogue as $index => $product)
                <article class="site-product-card {{ ($product['featured'] ?? false) ? 'is-featured' : '' }} {{ $section->variant === 'featured' && $index === 0 ? 'is-lead' : '' }}">
                    @if ($showImages && !empty($product['image']))
                        <div class="site-product-media"><img src="{{ $product['image'] }}" alt="{{ $product['alt'] ?: $product['title'] }}" loading="lazy"></div>
                    @endif
                    <div class="site-product-copy">
                        @if (($content['show_featured_badge'] ?? '1') === '1' && ($product['featured'] ?? false))
                            <p class="site-product-badge">{{ $content['featured_label'] ?: 'Featured' }}</p>
                        @endif
                        <h3>{{ $product['title'] }}</h3>
                        @if ($showDescriptions && !empty($product['description'])) <p class="site-product-description">{{ $product['description'] }}</p> @endif
                        @if ($showPrices && ($product['show_price'] ?? false) && $product['price'] !== null)
                            <p class="site-product-price">{{ $content['price_prefix'] ?? '' }}{{ number_format((float) $product['price'], 2) }}</p>
                        @endif
                        @if ($showButton)
                            <a class="site-button site-product-button" href="{{ $content['button_url'] }}">{{ $content['button_label'] ?: ($product['enquiry_label'] ?: 'Enquire now') }}</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
