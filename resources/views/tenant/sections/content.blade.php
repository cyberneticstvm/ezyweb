<div class="site-container site-content-grid">
    <div class="site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: \Illuminate\Support\Str::headline($section->type) }}</h2>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </div>
    <div class="site-content-art" aria-hidden="true"><span></span></div>
</div>
