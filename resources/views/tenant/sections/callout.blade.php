<div class="site-container site-callout-inner">
    <div class="site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: \Illuminate\Support\Str::headline($section->type) }}</h2>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </div>
    @if (!empty($content['primary_label']) && !empty($content['primary_url']))
        <a class="site-button site-button-accent" href="{{ $content['primary_url'] }}">{{ $content['primary_label'] }}</a>
    @endif
</div>
