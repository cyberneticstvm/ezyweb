@php
    $selected = collect(preg_split('/\R/', (string) ($content['team_keys'] ?? '')))->filter();
    $members = collect($items)
        ->when(($content['team_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn ($item) => $selected->contains($item['team_key'] ?? '')))
        ->when(!empty($content['team_role']), fn ($collection) => $collection->filter(fn ($item) => ($item['meta'] ?? '') === $content['team_role']))
        ->when(($content['team_featured_only'] ?? '0') === '1', fn ($collection) => $collection->filter(fn ($item) => ($item['featured'] ?? false) === true));
    $members = match ($content['team_sort'] ?? 'display-order') {
        'featured-first' => $members->sortByDesc(fn ($item) => (bool) ($item['featured'] ?? false)),
        'name' => $members->sortBy(fn ($item) => mb_strtolower((string) ($item['title'] ?? ''))),
        'role' => $members->sortBy(fn ($item) => mb_strtolower((string) ($item['meta'] ?? ''))),
        default => $members,
    };
    $members = $members->take((int) ($content['team_limit'] ?? 8))->values();
@endphp
<div
    class="site-container site-team-managed variant-{{ $section->variant }} align-cards-{{ $content['card_alignment'] ?? 'left' }} shape-{{ $content['image_shape'] ?? 'portrait' }}"
    style="--team-columns: {{ (int) ($content['team_columns'] ?? 4) }}"
>
    <header class="site-team-header">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    @if ($members->isNotEmpty())
        <div class="site-team-grid">
            @foreach ($members as $member)
                <article class="site-team-card {{ ($member['featured'] ?? false) ? 'is-featured' : '' }}">
                    @if (($content['show_photos'] ?? '1') === '1' && !empty($member['image']))
                        <div class="site-team-photo">
                            <img class="fit-{{ $content['image_fit'] ?? 'cover' }}" src="{{ $member['image'] }}" alt="Portrait of {{ $member['title'] }}" loading="lazy">
                        </div>
                    @endif
                    <div class="site-team-copy">
                        @if (($content['show_featured_badge'] ?? '0') === '1' && ($member['featured'] ?? false))
                            <p class="site-team-badge">{{ $content['featured_label'] ?: 'Leadership' }}</p>
                        @endif
                        <h3>{{ $member['title'] }}</h3>
                        @if (($content['show_roles'] ?? '1') === '1' && !empty($member['meta'])) <p class="site-team-role">{{ $member['meta'] }}</p> @endif
                        @if (($content['show_bios'] ?? '1') === '1' && !empty($member['description'])) <p class="site-team-bio">{{ $member['description'] }}</p> @endif
                        @if (($content['show_email'] ?? '0') === '1' || ($content['show_phone'] ?? '0') === '1' || ($content['show_linkedin'] ?? '1') === '1')
                            <div class="site-team-links">
                                @if (($content['show_email'] ?? '0') === '1' && !empty($member['email']))
                                    <a href="mailto:{{ $member['email'] }}">Email <span class="site-sr-only">{{ $member['title'] }}</span></a>
                                @endif
                                @if (($content['show_phone'] ?? '0') === '1' && !empty($member['phone']))
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $member['phone']) }}">{{ $member['phone'] }}</a>
                                @endif
                                @if (($content['show_linkedin'] ?? '1') === '1' && !empty($member['linkedin_url']))
                                    <a href="{{ $member['linkedin_url'] }}" target="_blank" rel="noopener noreferrer">LinkedIn <span class="site-sr-only">profile for {{ $member['title'] }}</span></a>
                                @endif
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
