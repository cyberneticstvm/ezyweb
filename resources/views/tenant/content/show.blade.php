<!DOCTYPE html>
<html lang="{{ $seo['language'] ?? 'en' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    @include('tenant.partials.seo-head')
    <title>{{ $seo['title'] }}</title>@vite('resources/css/app.css')
</head>
<body class="tenant-site">
    <a href="#main-content" class="site-skip-link">Skip to content</a>
    <main id="main-content" class="site-container py-16">
        <a href="/{{ $contentType }}">← Back to {{ $contentType }}</a>
        <article class="site-copy mt-8">
            @if (!empty($item['category'])) <p class="site-eyebrow">{{ $item['category'] }}</p> @endif
            <h1>{{ $item['title'] }}</h1>
            @if (!empty($item['featured_image'])) <img src="{{ $item['featured_image'] }}" alt="{{ $item['title'] ?? '' }}" class="mt-6" loading="eager"> @endif
            @if (!empty($item['excerpt'] ?? $item['summary'] ?? null)) <p class="site-lead">{{ $item['excerpt'] ?? $item['summary'] }}</p> @endif
            @if ($contentType === 'events')
                <aside class="mt-8 rounded-2xl border p-5">
                    @if (!empty($item['event_start_date']))
                        <p class="site-event-date">{{ \Illuminate\Support\Carbon::parse($item['event_start_date'])->format('D, M j, Y') }}@if (!empty($item['event_end_date']) && $item['event_end_date'] !== $item['event_start_date']) – {{ \Illuminate\Support\Carbon::parse($item['event_end_date'])->format('D, M j, Y') }}@endif</p>
                    @endif
                    @if (!empty($item['event_start_time']) || !empty($item['event_end_time'])) <p>{{ substr((string) $item['event_start_time'], 0, 5) }}@if (!empty($item['event_end_time']))–{{ substr($item['event_end_time'], 0, 5) }}@endif</p> @endif
                    @if (!empty($item['venue'])) <p class="site-event-venue">{{ $item['venue'] }}</p> @elseif (!empty($item['online_event_url'])) <p class="site-event-venue">Online event</p> @endif
                    @if (!empty($item['address'])) <p>{{ $item['address'] }}</p> @endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        @if (!empty($item['registration_url'])) <a class="site-button" href="{{ $item['registration_url'] }}">Register now</a> @endif
                        @if (!empty($item['online_event_url'])) <a class="site-button" href="{{ $item['online_event_url'] }}">Join online</a> @endif
                        @if (!empty($item['map_url'])) <a href="{{ $item['map_url'] }}">View map</a> @endif
                    </div>
                </aside>
            @endif
            @if ($contentType === 'careers')
                <aside class="mt-8 rounded-2xl border p-5">
                    <p class="site-career-meta">
                        @if (!empty($item['department'])) <span>{{ $item['department'] }}</span> @endif
                        @if (!empty($item['location'])) <span>{{ $item['location'] }}</span> @endif
                        @if (!empty($item['employment_type'])) <span>{{ \Illuminate\Support\Str::headline($item['employment_type']) }}</span> @endif
                        @if (!empty($item['workplace_type'])) <span>{{ \Illuminate\Support\Str::headline($item['workplace_type']) }}</span> @endif
                    </p>
                    @if (!empty($item['salary_display'])) <p class="site-career-salary">{{ $item['salary_display'] }}</p> @endif
                    @if (!empty($item['closing_date'])) <p>Apply by {{ \Illuminate\Support\Carbon::parse($item['closing_date'])->format('M j, Y') }}</p> @endif
                    @if (!empty($item['application_email'])) <p class="mt-4"><a class="site-button" href="mailto:{{ $item['application_email'] }}?subject={{ rawurlencode('Application: '.$item['title']) }}">Apply now</a></p> @endif
                </aside>
            @endif
            @if (!empty($item['body'] ?? $item['description'] ?? null)) <div class="mt-8">{!! app(\App\Content\RichTextSanitizer::class)->sanitize((string) ($item['body'] ?? $item['description'])) !!}</div> @endif
            @if ($contentType === 'careers' && !empty($item['responsibilities'])) <section class="mt-8"><h2>Responsibilities</h2><div>{!! app(\App\Content\RichTextSanitizer::class)->sanitize((string) $item['responsibilities']) !!}</div></section> @endif
            @if ($contentType === 'careers' && !empty($item['requirements'])) <section class="mt-8"><h2>Requirements</h2><div>{!! app(\App\Content\RichTextSanitizer::class)->sanitize((string) $item['requirements']) !!}</div></section> @endif
        </article>
        @if ($related->isNotEmpty())
            <section class="mt-14"><h2>Related</h2><div class="site-card-grid mt-6">
                @foreach ($related as $relatedItem)<article class="site-card"><h3><a href="/{{ $contentType }}/{{ $relatedItem['slug'] }}">{{ $relatedItem['title'] }}</a></h3></article>@endforeach
            </div></section>
        @endif
    </main>
</body></html>
