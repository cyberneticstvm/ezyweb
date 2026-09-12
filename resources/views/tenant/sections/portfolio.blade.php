@php
    $selected = collect(explode(',', (string) ($content['portfolio_slugs'] ?? '')))->filter();
    $projects = collect($items)
        ->when(($content['portfolio_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['slug'] ?? '')))
        ->when(!empty($content['portfolio_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['portfolio_category']))
        ->when(($content['portfolio_featured_only'] ?? '0') === '1', fn ($collection) => $collection->filter(fn ($item) => ($item['featured'] ?? false) === true));
    $projects = match ($content['portfolio_sort'] ?? 'display-order') {
        'featured-first' => $projects->sortByDesc(fn ($item) => (bool) ($item['featured'] ?? false)),
        'newest' => $projects->sortByDesc(fn ($item) => (string) ($item['project_date'] ?? '')),
        'oldest' => $projects->sortBy(fn ($item) => (string) ($item['project_date'] ?? '')),
        'title' => $projects->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        default => $projects,
    };
    $projects = $projects->take((int) ($content['portfolio_limit'] ?? 6))->values();
@endphp
<div
    class="site-container site-portfolio-managed variant-{{ $section->variant }}"
    style="--portfolio-columns: {{ (int) ($content['portfolio_columns'] ?? 3) }}"
>
    <header class="site-portfolio-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($projects->isNotEmpty())
        <div class="site-portfolio-grid">
            @foreach ($projects as $index => $project)
                @php
                    $external = Illuminate\Support\Str::startsWith((string) ($project['url'] ?? ''), ['http://', 'https://']);
                    $newTab = $external && ($content['open_new_tab'] ?? '0') === '1';
                    $date = !empty($project['project_date']) ? \Illuminate\Support\Carbon::parse($project['project_date'])->isoFormat('MMM YYYY') : '';
                @endphp
                <article class="site-portfolio-card {{ ($project['featured'] ?? false) ? 'is-featured' : '' }} {{ $section->variant === 'featured' && $index === 0 ? 'is-lead' : '' }}">
                    @if (($content['show_images'] ?? '1') === '1' && !empty($project['image']))
                        <div class="site-portfolio-media">
                            <img class="fit-{{ $content['image_fit'] ?? 'cover' }}" src="{{ $project['image'] }}" alt="{{ $project['alt'] ?: $project['title'] }}" loading="lazy">
                        </div>
                    @endif
                    <div class="site-portfolio-copy">
                        @if (($content['show_categories'] ?? '1') === '1' && !empty($project['meta']))
                            <p class="site-eyebrow">{{ $project['meta'] }}</p>
                        @endif
                        <h3>{{ $project['title'] }}</h3>
                        @if (($content['show_descriptions'] ?? '1') === '1' && !empty($project['description']))
                            <p>{{ $project['description'] }}</p>
                        @endif
                        @php
                            $details = array_filter([
                                ($content['show_clients'] ?? '1') === '1' ? ($project['client'] ?? '') : '',
                                ($content['show_dates'] ?? '1') === '1' ? $date : '',
                                ($content['show_locations'] ?? '0') === '1' ? ($project['location'] ?? '') : '',
                            ]);
                        @endphp
                        @if ($details !== []) <p class="site-portfolio-meta">{{ implode(' · ', $details) }}</p> @endif
                        @if (($content['show_button'] ?? '1') === '1' && !empty($project['url']))
                            <a class="site-portfolio-link" href="{{ $project['url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>
                                {{ $content['button_label'] ?: 'View project' }} <span aria-hidden="true">→</span>
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
