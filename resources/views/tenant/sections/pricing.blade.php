@php
    $plans = is_array($content['pricing_items'] ?? null) ? $content['pricing_items'] : [];
    $columns = in_array((string) ($content['pricing_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['pricing_columns'] : '2';
    $currencyAfter = ($content['currency_position'] ?? 'before') === 'after';
    $showDescriptions = ($content['show_plan_descriptions'] ?? '1') === '1';
    $showFeatures = ($content['show_plan_features'] ?? '1') === '1';
    $showButtons = ($content['show_plan_buttons'] ?? '1') === '1';
    $showBadges = ($content['show_plan_badges'] ?? '1') === '1';
    $showOriginal = ($content['show_original_prices'] ?? '1') === '1';
    $newTab = ($content['open_new_tab'] ?? '0') === '1';
@endphp
<div class="site-container site-pricing-managed variant-{{ $section->variant }}">
    <header class="site-pricing-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    <div class="site-pricing-grid" style="--pricing-columns: {{ $columns }}">
        @foreach ($plans as $plan)
            @php $features = preg_split('/\r\n|\r|\n/', $plan['features'] ?? '') ?: []; @endphp
            <article class="site-pricing-card {{ ($plan['is_featured'] ?? '0') === '1' ? 'is-featured' : '' }}">
                @if ($showBadges && !empty($plan['badge'])) <span class="site-pricing-badge">{{ $plan['badge'] }}</span> @endif
                <h3>{{ $plan['name'] ?? '' }}</h3>
                @if ($showDescriptions && !empty($plan['description'])) <p class="site-pricing-description">{{ $plan['description'] }}</p> @endif
                <div class="site-pricing-price">
                    @if ($showOriginal && !empty($plan['original_price'])) <del>{{ $currencyAfter ? '' : ($plan['currency'] ?? '') }}{{ $plan['original_price'] }}{{ $currencyAfter ? ($plan['currency'] ?? '') : '' }}</del> @endif
                    <p><span>{{ $currencyAfter ? '' : ($plan['currency'] ?? '') }}</span>{{ $plan['price'] ?? '' }}<span>{{ $currencyAfter ? ($plan['currency'] ?? '') : '' }}</span></p>
                    @if (!empty($plan['period'])) <small>{{ $plan['period'] }}</small> @endif
                </div>
                @if ($showFeatures && $features !== [])
                    <ul class="site-pricing-features">
                        @foreach ($features as $feature)
                            @php
                                $excluded = str_starts_with(trim($feature), '-');
                                $label = ltrim(trim($feature), '+- ');
                            @endphp
                            @if ($label !== '') <li class="{{ $excluded ? 'is-excluded' : '' }}"><span aria-hidden="true">{{ $excluded ? '×' : '✓' }}</span>{{ $label }}</li> @endif
                        @endforeach
                    </ul>
                @endif
                @if ($showButtons && !empty($plan['button_label']) && !empty($plan['button_url']))
                    <a class="site-button" href="{{ $plan['button_url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $plan['button_label'] }}</a>
                @endif
            </article>
        @endforeach
    </div>
    @if (!empty($content['pricing_note'])) <p class="site-pricing-note">{{ $content['pricing_note'] }}</p> @endif
</div>
