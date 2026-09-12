@php
    $counterItems = is_array($content['items'] ?? null) ? $content['items'] : [];
    $grouping = ($content['use_grouping'] ?? '1') === '1';
@endphp
<div
    class="site-container site-counters-managed variant-{{ $section->variant }}"
    data-counter-section
    data-animate="{{ ($content['animate'] ?? '1') === '1' ? 'true' : 'false' }}"
    data-duration="{{ (int) ($content['duration'] ?? 2) * 1000 }}"
    data-grouping="{{ $grouping ? 'true' : 'false' }}"
>
    <header class="site-counters-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($counterItems !== [])
        <div class="site-counter-grid" style="--counter-count: {{ min(count($counterItems), 4) }}">
            @foreach ($counterItems as $item)
                @php
                    $value = max(0, (int) ($item['value'] ?? 0));
                    $formattedValue = $grouping ? number_format($value) : (string) $value;
                    $accessibleValue = ($item['prefix'] ?? '').$formattedValue.($item['suffix'] ?? '').' '.($item['label'] ?? '');
                @endphp
                <article class="site-counter-item">
                    <p class="site-counter-value" aria-label="{{ $accessibleValue }}">
                        <span aria-hidden="true">{{ $item['prefix'] ?? '' }}<span data-counter-number data-value="{{ $value }}">{{ $formattedValue }}</span>{{ $item['suffix'] ?? '' }}</span>
                    </p>
                    <h3>{{ $item['label'] ?? '' }}</h3>
                    @if (!empty($item['description'])) <p class="site-counter-description">{{ $item['description'] }}</p> @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
