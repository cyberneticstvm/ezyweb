<div class="site-container">
    <div class="site-copy site-section-heading">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: \Illuminate\Support\Str::headline($section->type) }}</h2>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </div>
    @if (!empty($items))
        <div class="site-card-grid">
            @foreach ($items as $item)
                <article class="site-card">
                    @if (!empty($item['image']))
                        <img src="{{ $item['image'] }}" alt="{{ $item['alt'] }}" loading="lazy">
                    @endif
                    @if (!empty($item['meta'])) <p class="site-eyebrow">{{ $item['meta'] }}</p> @endif
                    <h3>{{ $item['title'] }}</h3>
                    @if (!empty($item['description'])) <p>{{ $item['description'] }}</p> @endif
                    @if (!empty($item['url'])) <a href="{{ $item['url'] }}">Learn more</a> @endif
                </article>
            @endforeach
        </div>
    @else
        <div class="site-card-grid" aria-hidden="true">
            @foreach ([1, 2, 3] as $index)
                <div class="site-card"><span>0{{ $index }}</span><i></i><i></i></div>
            @endforeach
        </div>
    @endif
</div>
