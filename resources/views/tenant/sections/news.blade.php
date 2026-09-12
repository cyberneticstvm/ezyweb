@php
    $selected = collect(preg_split('/\R/', (string) ($content['news_slugs'] ?? '')))->filter();
    $articles = collect($items)
        ->when(($content['news_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['slug'] ?? '')))
        ->when(!empty($content['news_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['news_category']));
    $articles = match ($content['news_sort'] ?? 'newest') {
        'oldest' => $articles->sortBy('published_at'),
        'title' => $articles->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        default => $articles->sortByDesc('published_at'),
    };
    $articles = $articles->take((int) ($content['news_limit'] ?? 6))->values();
@endphp
<div class="site-container site-news-managed variant-{{ $section->variant }}">
    <header class="site-blog-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($articles->isNotEmpty())
        <div class="site-blog-grid columns-{{ $content['news_columns'] ?? '3' }}">
            @foreach ($articles as $article)
                <article class="site-blog-card">
                    @if (($content['show_images'] ?? '1') === '1' && !empty($article['image']))
                        <a href="{{ $article['url'] }}" class="site-blog-image"><img src="{{ $article['image'] }}" alt="{{ $article['alt'] }}" loading="lazy" style="object-fit: {{ $content['image_fit'] ?? 'cover' }}"></a>
                    @endif
                    <div class="site-blog-body">
                        @if (($content['show_categories'] ?? '1') === '1' && !empty($article['meta'])) <p class="site-eyebrow">{{ $article['meta'] }}</p> @endif
                        <h3><a href="{{ $article['url'] }}">{{ $article['title'] }}</a></h3>
                        @if (($content['show_excerpts'] ?? '1') === '1' && !empty($article['description'])) <p>{{ $article['description'] }}</p> @endif
                        @if (($content['show_authors'] ?? '1') === '1' || ($content['show_dates'] ?? '1') === '1' || ($content['show_reading_time'] ?? '0') === '1')
                            <p class="site-blog-meta">
                                @if (($content['show_authors'] ?? '1') === '1' && !empty($article['author_name'])) <span>By {{ $article['author_name'] }}</span> @endif
                                @if (($content['show_dates'] ?? '1') === '1' && !empty($article['published_at'])) <time datetime="{{ $article['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($article['published_at'])->format('M j, Y') }}</time> @endif
                                @if (($content['show_reading_time'] ?? '0') === '1') <span>{{ $article['reading_minutes'] }} min read</span> @endif
                            </p>
                        @endif
                        @if (($content['show_tags'] ?? '0') === '1' && !empty($article['tags'])) <p class="site-blog-tags">{{ collect($article['tags'])->map(fn ($tag) => '#'.$tag)->implode(' ') }}</p> @endif
                        @if (!empty($content['read_more_label'])) <a class="site-blog-link" href="{{ $article['url'] }}">{{ $content['read_more_label'] }} →</a> @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p>No published news articles match this section’s filters.</p>
    @endif
    @if (($content['show_archive_link'] ?? '1') === '1' && !empty($content['archive_label']))
        <p class="site-blog-archive"><a class="site-button" href="/news">{{ $content['archive_label'] }}</a></p>
    @endif
</div>
