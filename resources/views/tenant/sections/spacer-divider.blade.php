@php
    $before = in_array((string) ($content['space_before'] ?? ''), ['0', '16', '32', '48', '64', '96', '128'], true) ? (int) $content['space_before'] : 48;
    $after = in_array((string) ($content['space_after'] ?? ''), ['0', '16', '32', '48', '64', '96', '128'], true) ? (int) $content['space_after'] : 48;
    $weight = in_array((string) ($content['line_weight'] ?? ''), ['1', '2', '3', '4'], true) ? (int) $content['line_weight'] : 1;
    $width = in_array($content['line_width'] ?? '', ['narrow', 'medium', 'wide'], true) ? $content['line_width'] : 'full';
    $style = in_array($content['line_style'] ?? '', ['dashed', 'dotted'], true) ? $content['line_style'] : 'solid';
    $color = in_array($content['line_color'] ?? '', ['primary', 'accent'], true) ? $content['line_color'] : 'border';
    $label = $content['line_label'] ?? '';
@endphp
<div class="site-container site-divider-managed variant-{{ $section->variant }} width-{{ $width }} style-{{ $style }} color-{{ $color }} {{ $label !== '' ? 'has-label' : '' }}" style="--divider-before: {{ $before }}px; --divider-after: {{ $after }}px; --divider-weight: {{ $weight }}px;">
    @if ($section->variant !== 'space')
        <div class="site-divider-rule {{ $label !== '' ? 'has-label' : '' }}" role="separator" @if ($label !== '') aria-label="{{ $label }}" @endif>
            <span class="site-divider-stroke" aria-hidden="true"></span>
            @if ($label !== '')
                <span class="site-divider-label" aria-hidden="true">{{ $label }}</span>
                <span class="site-divider-stroke" aria-hidden="true"></span>
            @endif
        </div>
    @endif
</div>
