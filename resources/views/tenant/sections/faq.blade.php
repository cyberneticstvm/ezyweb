@php
    $selected = collect(preg_split('/\R/', (string) ($content['faq_keys'] ?? '')))->filter();
    $faqs = collect($items)
        ->when(($content['faq_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['faq_key'] ?? '')))
        ->when(!empty($content['faq_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['faq_category']));
    $faqs = match ($content['faq_sort'] ?? 'display-order') {
        'question' => $faqs->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        'category' => $faqs->sortBy(fn ($item) => mb_strtolower((string) ($item['meta'] ?? ''))),
        default => $faqs,
    };
    $faqs = $faqs->take((int) ($content['faq_limit'] ?? 12))->values();
    $allowMultiple = ($content['allow_multiple'] ?? '1') === '1';
@endphp
<div
    class="site-container site-faq-managed variant-{{ $section->variant }}"
    data-faq-section
    data-allow-multiple="{{ $allowMultiple ? 'true' : 'false' }}"
>
    <header class="site-faq-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($faqs->isNotEmpty())
        @if (($content['enable_search'] ?? '0') === '1' || ($content['show_expand_controls'] ?? '0') === '1')
            <div class="site-faq-tools">
                @if (($content['enable_search'] ?? '0') === '1')
                    <label class="site-faq-search">
                        <span class="site-sr-only">Search frequently asked questions</span>
                        <input type="search" data-faq-search placeholder="{{ $content['search_placeholder'] ?: 'Search questions…' }}" autocomplete="off">
                    </label>
                @endif
                @if (($content['show_expand_controls'] ?? '0') === '1')
                    <div class="site-faq-tool-buttons">
                        <button type="button" data-faq-expand>Expand all</button>
                        <button type="button" data-faq-collapse>Collapse all</button>
                    </div>
                @endif
            </div>
        @endif
        <div class="site-faq-list">
            @foreach ($faqs as $index => $faq)
                @php
                    $open = ($content['default_open'] ?? 'first') === 'all' || (($content['default_open'] ?? 'first') === 'first' && $index === 0);
                    if (!$allowMultiple && $index > 0) $open = false;
                @endphp
                <details class="site-faq-item" data-faq-item data-search="{{ mb_strtolower(implode(' ', array_filter([$faq['title'], $faq['description'], $faq['meta'] ?? '']))) }}" @if ($open) open @endif>
                    <summary>
                        @if (($content['show_numbers'] ?? '0') === '1') <span class="site-faq-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span> @endif
                        <span class="site-faq-question">
                            @if (($content['show_categories'] ?? '1') === '1' && !empty($faq['meta'])) <small>{{ $faq['meta'] }}</small> @endif
                            <strong>{{ $faq['title'] }}</strong>
                        </span>
                        <span class="site-faq-icon" aria-hidden="true"></span>
                    </summary>
                    <div class="site-faq-answer"><p>{{ $faq['description'] }}</p></div>
                </details>
            @endforeach
        </div>
        <p class="site-faq-empty" data-faq-empty hidden>No questions match your search.</p>
    @endif
    @if (($content['show_cta'] ?? '0') === '1' && (!empty($content['cta_text']) || !empty($content['cta_label'])))
        <aside class="site-faq-cta">
            @if (!empty($content['cta_text'])) <p>{{ $content['cta_text'] }}</p> @endif
            @if (!empty($content['cta_label']) && !empty($content['cta_url'])) <a class="site-button" href="{{ $content['cta_url'] }}">{{ $content['cta_label'] }}</a> @endif
        </aside>
    @endif
</div>
