@php
    $stats = is_array($content['stats'] ?? null) ? $content['stats'] : [];
    $columns = in_array((string) ($content['stats_columns'] ?? ''), ['2', '3', '4'], true) ? (int) $content['stats_columns'] : 4;
    $grouping = ($content['use_grouping'] ?? '1') === '1';
    $highlightFirst = ($content['highlight_first'] ?? '0') === '1';
@endphp
<div
    class="site-container site-statistics-managed variant-{{ $section->variant }}"
    data-counter-section
    data-animate="{{ ($content['animate'] ?? '1') === '1' ? 'true' : 'false' }}"
    data-duration="{{ (int) ($content['duration'] ?? 2) * 1000 }}"
    data-grouping="{{ $grouping ? 'true' : 'false' }}"
>
    <header class="site-statistics-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($stats !== [])
        <div class="site-statistics-grid" style="--stats-columns: {{ $columns }};">
            @foreach ($stats as $item)
                @php
                    $rawValue = $item['value'] ?? '0';
                    $decimals = str_contains($rawValue, '.') ? strlen(explode('.', $rawValue, 2)[1]) : 0;
                    $formattedValue = number_format((float) $rawValue, $decimals, '.', $grouping ? ',' : '');
                    $accessibleValue = ($item['prefix'] ?? '').$formattedValue.($item['suffix'] ?? '').' '.($item['label'] ?? '');
                @endphp
                <article class="site-statistics-item {{ $highlightFirst && $loop->first ? 'is-highlighted' : '' }}">
                    <p class="site-statistics-value" aria-label="{{ $accessibleValue }}">
                        <span aria-hidden="true">{{ $item['prefix'] ?? '' }}<span data-counter-number data-value="{{ $rawValue }}" data-decimals="{{ $decimals }}">{{ $formattedValue }}</span>{{ $item['suffix'] ?? '' }}</span>
                    </p>
                    <h3>{{ $item['label'] ?? '' }}</h3>
                    @if (($content['show_descriptions'] ?? '1') === '1' && !empty($item['description']))
                        <p class="site-statistics-description">{{ $item['description'] }}</p>
                    @endif
                    @if (($content['show_trends'] ?? '1') === '1' && !empty($item['trend_text']))
                        <p class="site-statistics-trend direction-{{ $item['trend_direction'] ?? 'neutral' }}">
                            @if (($item['trend_direction'] ?? 'neutral') === 'up') <span aria-hidden="true">↑ </span><span class="site-sr-only">Up: </span> @endif
                            @if (($item['trend_direction'] ?? 'neutral') === 'down') <span aria-hidden="true">↓ </span><span class="site-sr-only">Down: </span> @endif
                            {{ $item['trend_text'] }}
                        </p>
                    @endif
                    @if (($content['show_sources'] ?? '1') === '1' && (!empty($item['source_label']) || !empty($item['source_url'])))
                        <p class="site-statistics-source">
                            @if (!empty($item['source_url']))
                                <a href="{{ $item['source_url'] }}">Source: {{ $item['source_label'] ?: 'View source' }}</a>
                            @else
                                Source: {{ $item['source_label'] }}
                            @endif
                        </p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
