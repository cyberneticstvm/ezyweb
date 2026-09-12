@php
    $steps = is_array($content['process_items'] ?? null) ? $content['process_items'] : [];
    $columns = in_array((string) ($content['process_columns'] ?? ''), ['2', '3', '4'], true) ? (string) $content['process_columns'] : '3';
    $showNumbers = ($content['show_step_numbers'] ?? '1') === '1';
    $showIcons = ($content['show_step_icons'] ?? '0') === '1';
    $showImages = ($content['show_step_images'] ?? '1') === '1';
    $showDescriptions = ($content['show_step_descriptions'] ?? '1') === '1';
    $showMeta = ($content['show_step_meta'] ?? '0') === '1';
    $showLinks = ($content['show_step_links'] ?? '1') === '1';
    $newTab = ($content['open_new_tab'] ?? '0') === '1';
    $fit = ($content['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
    $alignment = ($content['process_alignment'] ?? 'left') === 'center' ? 'center' : 'left';
    $connector = in_array($content['process_connector'] ?? null, ['solid', 'dashed', 'none'], true) ? $content['process_connector'] : 'solid';
@endphp
<div class="site-container site-process-managed variant-{{ $section->variant }} align-steps-{{ $alignment }} connector-{{ $connector }}">
    <header class="site-process-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    <ol class="site-process-list" style="--process-columns: {{ $columns }}">
        @foreach ($steps as $index => $step)
            <li class="site-process-step">
                <div class="site-process-marker">
                    @if ($showNumbers)<span class="site-process-number">{{ $step['number'] ?? str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>@endif
                    @if ($showIcons && !empty($step['icon']))<span class="site-process-icon" aria-hidden="true">{{ $step['icon'] }}</span>@endif
                </div>
                @if ($showImages && !empty($step['image_id']))
                    <img class="site-process-image fit-{{ $fit }}" src="{{ route('media.show', ['media' => $step['image_id'], 'variant' => 'medium'], absolute: false) }}" alt="{{ $step['image_alt'] ?? '' }}" loading="lazy">
                @endif
                @if ($showMeta && !empty($step['meta'])) <p class="site-process-meta">{{ $step['meta'] }}</p> @endif
                <h3>{{ $step['title'] ?? '' }}</h3>
                @if ($showDescriptions && !empty($step['description'])) <p class="site-process-description">{{ $step['description'] }}</p> @endif
                @if ($showLinks && !empty($step['link_label']) && !empty($step['link_url']))
                    <a href="{{ $step['link_url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $step['link_label'] }} <span aria-hidden="true">→</span></a>
                @endif
            </li>
        @endforeach
    </ol>
</div>
