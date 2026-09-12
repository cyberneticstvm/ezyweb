@php
    $featureItems = is_array($content['feature_items'] ?? null) ? $content['feature_items'] : [];
    $columns = in_array((string) ($content['columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['columns'] : '3';
    $showIcons = ($content['show_icons'] ?? '1') === '1';
    $showImages = ($content['show_images'] ?? '1') === '1';
    $showDescriptions = ($content['show_descriptions'] ?? '1') === '1';
    $showLinks = ($content['show_links'] ?? '1') === '1';
    $newTab = ($content['open_new_tab'] ?? '0') === '1';
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $alignment = ($content['card_alignment'] ?? 'left') === 'center' ? 'center' : 'left';
@endphp
<div class="site-container site-features-managed variant-{{ $section->variant }} align-cards-{{ $alignment }}">
    <header class="site-features-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    <div class="site-feature-grid" style="--feature-columns: {{ $columns }}">
        @foreach ($featureItems as $index => $item)
            @php $hasImage = $showImages && !empty($item['image_id']); @endphp
            <article class="site-feature-card {{ ($item['is_featured'] ?? '0') === '1' ? 'is-featured' : '' }}">
                @if (($item['is_featured'] ?? '0') === '1' && !empty($content['featured_label']))
                    <span class="site-feature-badge">{{ $content['featured_label'] }}</span>
                @endif
                @if ($hasImage)
                    <img class="site-feature-image fit-{{ $fit }}" src="{{ route('media.show', ['media' => $item['image_id'], 'variant' => 'medium'], absolute: false) }}" alt="{{ $item['image_alt'] ?? '' }}" loading="lazy">
                @elseif ($showIcons && !empty($item['icon']))
                    <span class="site-feature-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                @elseif ($showIcons)
                    <span class="site-feature-icon" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                @endif
                <h3>{{ $item['title'] ?? '' }}</h3>
                @if ($showDescriptions && !empty($item['description'])) <p>{{ $item['description'] }}</p> @endif
                @if ($showLinks && !empty($item['link_label']) && !empty($item['link_url']))
                    <a href="{{ $item['link_url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $item['link_label'] }} <span aria-hidden="true">→</span></a>
                @endif
            </article>
        @endforeach
    </div>
</div>
