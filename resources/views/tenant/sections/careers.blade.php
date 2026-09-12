@php
    $selected = collect(preg_split('/\R/', (string) ($content['career_slugs'] ?? '')))->filter();
    $jobs = collect($items)
        ->when(($content['career_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['slug'] ?? '')))
        ->when(!empty($content['career_department']), fn ($collection) => $collection->filter(fn ($item) => ($item['department'] ?? '') === $content['career_department']))
        ->when(!empty($content['career_location']), fn ($collection) => $collection->filter(fn ($item) => ($item['location'] ?? '') === $content['career_location']))
        ->when(!empty($content['career_employment_type']), fn ($collection) => $collection->filter(fn ($item) => ($item['employment_type'] ?? '') === $content['career_employment_type']))
        ->when(!empty($content['career_workplace_type']), fn ($collection) => $collection->filter(fn ($item) => ($item['workplace_type'] ?? '') === $content['career_workplace_type']));
    $jobs = match ($content['career_sort'] ?? 'display-order') {
        'title' => $jobs->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        'closing-soonest' => $jobs->sortBy(fn ($item) => $item['closing_date'] ?: '9999-12-31'),
        'location' => $jobs->sortBy(fn ($item) => mb_strtolower((string) ($item['location'] ?? ''))),
        default => $jobs,
    };
    $jobs = $jobs->take((int) ($content['career_limit'] ?? 8))->values();
@endphp
<div class="site-container site-careers-managed variant-{{ $section->variant }}">
    <header class="site-blog-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($jobs->isNotEmpty())
        <div class="site-career-grid columns-{{ $content['career_columns'] ?? '2' }}">
            @foreach ($jobs as $index => $job)
                <article class="site-career-card @if ($section->variant === 'featured' && $index === 0) is-featured @endif">
                    <div>
                        @if (($content['show_departments'] ?? '1') === '1' && !empty($job['department'])) <p class="site-eyebrow">{{ $job['department'] }}</p> @endif
                        <h3><a href="{{ $job['url'] }}">{{ $job['title'] }}</a></h3>
                        <p class="site-career-meta">
                            @if (($content['show_locations'] ?? '1') === '1' && !empty($job['location'])) <span>{{ $job['location'] }}</span> @endif
                            @if (($content['show_employment_types'] ?? '1') === '1' && !empty($job['employment_type'])) <span>{{ \Illuminate\Support\Str::headline($job['employment_type']) }}</span> @endif
                            @if (($content['show_workplace_types'] ?? '1') === '1' && !empty($job['workplace_type'])) <span>{{ \Illuminate\Support\Str::headline($job['workplace_type']) }}</span> @endif
                        </p>
                        @if (($content['show_summaries'] ?? '1') === '1' && !empty($job['description'])) <p>{{ $job['description'] }}</p> @endif
                        @if (($content['show_salaries'] ?? '1') === '1' && !empty($job['salary_display'])) <p class="site-career-salary">{{ $job['salary_display'] }}</p> @endif
                        @if (($content['show_closing_dates'] ?? '1') === '1' && !empty($job['closing_date'])) <p class="site-blog-meta">Apply by {{ \Illuminate\Support\Carbon::parse($job['closing_date'])->format('M j, Y') }}</p> @endif
                    </div>
                    @if (($content['show_apply_button'] ?? '1') === '1' && !empty($content['apply_label'])) <a class="site-button" href="{{ $job['url'] }}">{{ $content['apply_label'] }}</a> @endif
                </article>
            @endforeach
        </div>
    @else
        <p>No open positions match this section’s filters.</p>
    @endif
    @if (($content['show_archive_link'] ?? '1') === '1' && !empty($content['archive_label'])) <p class="site-blog-archive"><a class="site-button" href="/careers">{{ $content['archive_label'] }}</a></p> @endif
</div>
