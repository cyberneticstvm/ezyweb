@php
    $url = $content['video_url'] ?? '';
    $provider = $content['video_provider'] ?? '';
    $poster = $content['poster_image_id'] ?? '';
    $ratio = in_array($content['aspect_ratio'] ?? '', ['standard', 'vertical'], true) ? $content['aspect_ratio'] : 'wide';
    $providerLabel = match ($provider) { 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'file' => 'video file', default => 'video' };
    $title = ($content['video_title'] ?? '') ?: (($content['heading'] ?? '') ?: 'Watch video');
@endphp
<div class="site-container site-video-section variant-{{ $section->variant }}">
    <div class="site-video-intro site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        @if (!empty($content['heading'])) <h2>{{ $content['heading'] }}</h2> @endif
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </div>
    @if ($url)
        <figure class="site-video-figure ratio-{{ $ratio }}">
            @if ($provider === 'file')
                <video
                    class="site-video-native"
                    src="{{ $url }}"
                    @if ($poster) poster="{{ route('media.show', ['media' => $poster, 'variant' => 'large'], absolute: false) }}" @endif
                    aria-label="{{ $title }}"
                    preload="none"
                    playsinline
                    @if (($content['autoplay'] ?? '0') === '1') autoplay @endif
                    @if (($content['muted'] ?? '0') === '1') muted @endif
                    @if (($content['loop'] ?? '0') === '1') loop @endif
                    @if (($content['show_controls'] ?? '1') === '1') controls @endif
                ><a href="{{ $url }}" target="_blank" rel="noopener noreferrer">Open video file</a></video>
            @else
                <div class="site-video-embed" data-video-embed data-video-provider="{{ $provider }}" data-video-id="{{ $content['video_id'] ?? '' }}" data-video-muted="{{ $content['muted'] ?? '0' }}" data-video-loop="{{ $content['loop'] ?? '0' }}" data-video-controls="{{ $content['show_controls'] ?? '1' }}" data-video-title="{{ $title }}">
                    <a class="site-video-watch" href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="Play {{ $title }} here (loads {{ $providerLabel }})">
                        @if ($poster)
                            <img src="{{ route('media.show', ['media' => $poster, 'variant' => 'large'], absolute: false) }}" alt="{{ $content['poster_alt'] ?? '' }}" loading="lazy">
                        @else
                            <span class="site-video-fallback" aria-hidden="true"></span>
                        @endif
                        <span class="site-video-play" aria-hidden="true">▶</span>
                        <span class="site-video-watch-label">Play video · loads {{ $providerLabel }}</span>
                    </a>
                </div>
            @endif
            @if (($content['show_caption'] ?? '1') === '1' && !empty($content['video_caption']))
                <figcaption>{{ $content['video_caption'] }}</figcaption>
            @endif
            @if ($provider !== 'file')
                <a class="site-video-external" href="{{ $url }}" target="_blank" rel="noopener noreferrer">Open on {{ $providerLabel }} <span aria-hidden="true">↗</span></a>
            @endif
        </figure>
    @elseif (!empty($previewMode))
        <div class="site-video-empty">Add a YouTube, Vimeo, or public GCS video URL in Website Design.</div>
    @endif
    @if ((!empty($content['transcript_url']) && !empty($content['transcript_label'])) || (!empty($content['primary_url']) && !empty($content['primary_label'])))
        <div class="site-video-links">
            @if (!empty($content['transcript_url']) && !empty($content['transcript_label']))
                <a href="{{ $content['transcript_url'] }}">{{ $content['transcript_label'] }}</a>
            @endif
            @if (!empty($content['primary_url']) && !empty($content['primary_label']))
                <a class="site-button" href="{{ $content['primary_url'] }}" @if (($content['primary_new_tab'] ?? '0') === '1') target="_blank" rel="noopener noreferrer" @endif>{{ $content['primary_label'] }}</a>
            @endif
        </div>
    @endif
</div>
