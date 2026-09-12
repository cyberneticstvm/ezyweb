@php
    $selected = collect(preg_split('/\R/', (string) ($content['event_slugs'] ?? '')))->filter();
    $today = now()->startOfDay();
    $events = collect($items)
        ->when(($content['event_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['slug'] ?? '')))
        ->when(!empty($content['event_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['event_category']))
        ->filter(function ($event) use ($content, $today) {
            if (($content['event_timeframe'] ?? 'upcoming') === 'all') return true;
            $boundary = $event['event_end_date'] ?: $event['event_start_date'];
            if (!$boundary) return false;
            $upcoming = \Illuminate\Support\Carbon::parse($boundary)->startOfDay()->gte($today);
            return ($content['event_timeframe'] ?? 'upcoming') === 'upcoming' ? $upcoming : !$upcoming;
        });
    $events = match ($content['event_sort'] ?? 'soonest') {
        'latest' => $events->sortByDesc('event_start_date'),
        'title' => $events->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        default => $events->sortBy('event_start_date'),
    };
    $events = $events->take((int) ($content['event_limit'] ?? 6))->values();
@endphp
<div class="site-container site-events-managed variant-{{ $section->variant }}">
    <header class="site-blog-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($events->isNotEmpty())
        <div class="site-blog-grid columns-{{ $content['event_columns'] ?? '3' }}">
            @foreach ($events as $event)
                @php $actionUrl = $event['registration_url'] ?: $event['online_event_url'] ?: $event['url']; @endphp
                <article class="site-blog-card site-event-card">
                    @if (($content['show_images'] ?? '1') === '1' && !empty($event['image']))
                        <a href="{{ $event['url'] }}" class="site-blog-image"><img src="{{ $event['image'] }}" alt="{{ $event['alt'] }}" loading="lazy" style="object-fit: {{ $content['image_fit'] ?? 'cover' }}"></a>
                    @endif
                    <div class="site-blog-body">
                        @if (($content['show_categories'] ?? '1') === '1' && !empty($event['meta'])) <p class="site-eyebrow">{{ $event['meta'] }}</p> @endif
                        <h3><a href="{{ $event['url'] }}">{{ $event['title'] }}</a></h3>
                        @if (($content['show_dates'] ?? '1') === '1' && !empty($event['event_start_date']))
                            <p class="site-event-date"><time datetime="{{ $event['event_start_date'] }}">{{ \Illuminate\Support\Carbon::parse($event['event_start_date'])->format('D, M j, Y') }}</time>@if (!empty($event['event_end_date']) && $event['event_end_date'] !== $event['event_start_date']) <span>– {{ \Illuminate\Support\Carbon::parse($event['event_end_date'])->format('D, M j, Y') }}</span>@endif</p>
                        @endif
                        @if (($content['show_times'] ?? '1') === '1' && (!empty($event['event_start_time']) || !empty($event['event_end_time']))) <p class="site-blog-meta"><span>{{ substr($event['event_start_time'], 0, 5) }}@if (!empty($event['event_end_time']))–{{ substr($event['event_end_time'], 0, 5) }}@endif</span></p> @endif
                        @if (($content['show_venues'] ?? '1') === '1' && (!empty($event['venue']) || !empty($event['online_event_url']))) <p class="site-event-venue">{{ $event['venue'] ?: 'Online event' }}</p> @endif
                        @if (($content['show_addresses'] ?? '0') === '1' && !empty($event['address'])) <p class="site-blog-meta">{{ $event['address'] }}</p> @endif
                        @if (($content['show_excerpts'] ?? '1') === '1' && !empty($event['description'])) <p>{{ $event['description'] }}</p> @endif
                        @if (($content['show_registration'] ?? '1') === '1' && !empty($content['registration_label'])) <a class="site-blog-link" href="{{ $actionUrl }}">{{ $content['registration_label'] }} →</a> @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p>No published events match this section’s filters.</p>
    @endif
    @if (($content['show_archive_link'] ?? '1') === '1' && !empty($content['archive_label'])) <p class="site-blog-archive"><a class="site-button" href="/events">{{ $content['archive_label'] }}</a></p> @endif
</div>
