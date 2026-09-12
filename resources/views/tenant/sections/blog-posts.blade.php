@php
    $selected = collect(preg_split('/\R/', (string) ($content['blog_slugs'] ?? '')))->filter();
    $posts = collect($items)
        ->when(($content['blog_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['slug'] ?? '')))
        ->when(!empty($content['blog_category']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['blog_category']));
    $posts = match ($content['blog_sort'] ?? 'newest') {
        'oldest' => $posts->sortBy('published_at'),
        'title' => $posts->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        default => $posts->sortByDesc('published_at'),
    };
    $posts = $posts->take((int) ($content['blog_limit'] ?? 6))->values();
@endphp
<div class="site-container site-blog-managed variant-{{ $section->variant }}">
    <header class="site-blog-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($posts->isNotEmpty())
        <div class="site-blog-grid columns-{{ $content['blog_columns'] ?? '3' }}">
            @foreach ($posts as $post)
                <article class="site-blog-card">
                    @if (($content['show_images'] ?? '1') === '1' && !empty($post['image']))
                        <a href="{{ $post['url'] }}" class="site-blog-image"><img src="{{ $post['image'] }}" alt="{{ $post['alt'] }}" loading="lazy" style="object-fit: {{ $content['image_fit'] ?? 'cover' }}"></a>
                    @endif
                    <div class="site-blog-body">
                        @if (($content['show_categories'] ?? '1') === '1' && !empty($post['meta'])) <p class="site-eyebrow">{{ $post['meta'] }}</p> @endif
                        <h3><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
                        @if (($content['show_excerpts'] ?? '1') === '1' && !empty($post['description'])) <p>{{ $post['description'] }}</p> @endif
                        @if (($content['show_authors'] ?? '1') === '1' || ($content['show_dates'] ?? '1') === '1' || ($content['show_reading_time'] ?? '1') === '1')
                            <p class="site-blog-meta">
                                @if (($content['show_authors'] ?? '1') === '1' && !empty($post['author_name'])) <span>By {{ $post['author_name'] }}</span> @endif
                                @if (($content['show_dates'] ?? '1') === '1' && !empty($post['published_at'])) <time datetime="{{ $post['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($post['published_at'])->format('M j, Y') }}</time> @endif
                                @if (($content['show_reading_time'] ?? '1') === '1') <span>{{ $post['reading_minutes'] }} min read</span> @endif
                            </p>
                        @endif
                        @if (($content['show_tags'] ?? '0') === '1' && !empty($post['tags'])) <p class="site-blog-tags">{{ collect($post['tags'])->map(fn ($tag) => '#'.$tag)->implode(' ') }}</p> @endif
                        @if (!empty($content['read_more_label'])) <a class="site-blog-link" href="{{ $post['url'] }}">{{ $content['read_more_label'] }} →</a> @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <p>No published blog posts match this section’s filters.</p>
    @endif
    @if (($content['show_archive_link'] ?? '1') === '1' && !empty($content['archive_label']))
        <p class="site-blog-archive"><a class="site-button" href="/blog">{{ $content['archive_label'] }}</a></p>
    @endif
</div>
