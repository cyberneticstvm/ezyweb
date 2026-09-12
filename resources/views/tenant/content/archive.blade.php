<!DOCTYPE html>
<html lang="{{ $seo['language'] ?? 'en' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    @include('tenant.partials.seo-head')<title>{{ $seo['title'] }}</title>
    @if ($items->previousPageUrl())<link rel="prev" href="{{ $items->previousPageUrl() }}">@endif
    @if ($items->nextPageUrl())<link rel="next" href="{{ $items->nextPageUrl() }}">@endif
    @vite('resources/css/app.css')
</head>
<body class="tenant-site">
    <a href="#main-content" class="site-skip-link">Skip to content</a>
    <main id="main-content" class="site-container py-16">
        <p class="site-eyebrow">{{ $website->name }}</p><h1>{{ $title }}</h1>
        <form method="get" class="mt-6 flex flex-wrap gap-3" role="search">
            <label><span class="sr-only">Search {{ $title }}</span><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search {{ strtolower($title) }}" class="rounded-xl border px-4 py-2.5"></label>
            <button class="rounded-xl bg-slate-950 px-4 py-2.5 font-semibold text-white">Search</button>
        </form>
        <div class="site-card-grid mt-10">
            @forelse ($items as $item)
                <article class="site-card">
                    @if (!empty($item['featured_image'])) <img src="{{ $item['featured_image'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy"> @endif
                    @if (!empty($item['category'])) <a class="site-eyebrow" href="?category={{ urlencode($item['category']) }}">{{ $item['category'] }}</a> @endif
                    <h2><a href="/{{ $contentType }}/{{ $item['slug'] }}">{{ $item['title'] }}</a></h2>
                    @if ($contentType === 'events' && !empty($item['event_start_date']))
                        <p class="site-event-date">{{ \Illuminate\Support\Carbon::parse($item['event_start_date'])->format('D, M j, Y') }}@if (!empty($item['event_start_time'])) · {{ substr($item['event_start_time'], 0, 5) }}@endif</p>
                        @if (!empty($item['venue']) || !empty($item['online_event_url'])) <p class="site-event-venue">{{ $item['venue'] ?: 'Online event' }}</p> @endif
                    @endif
                    @if ($contentType === 'careers')
                        <p class="site-career-meta">
                            @if (!empty($item['department'])) <span>{{ $item['department'] }}</span> @endif
                            @if (!empty($item['location'])) <span>{{ $item['location'] }}</span> @endif
                            @if (!empty($item['employment_type'])) <span>{{ \Illuminate\Support\Str::headline($item['employment_type']) }}</span> @endif
                            @if (!empty($item['workplace_type'])) <span>{{ \Illuminate\Support\Str::headline($item['workplace_type']) }}</span> @endif
                        </p>
                        @if (!empty($item['salary_display'])) <p class="site-career-salary">{{ $item['salary_display'] }}</p> @endif
                    @endif
                    @if (!empty($item['excerpt'] ?? $item['summary'] ?? null)) <p>{{ $item['excerpt'] ?? $item['summary'] }}</p> @endif
                </article>
            @empty
                <p>No matching published content is available.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </main>
</body></html>
