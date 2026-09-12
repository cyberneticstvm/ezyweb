@php
    $settings = $contactSettings ?? [];
    $address = is_string($settings['address'] ?? null) ? trim($settings['address']) : '';
    $hours = is_string($settings['working_hours'] ?? null) ? trim($settings['working_hours']) : '';
    $embedUrl = is_string($settings['maps_embed_url'] ?? null) ? trim($settings['maps_embed_url']) : '';
    $showMap = ($content['show_map'] ?? '1') === '1' && $embedUrl !== '';
    $showAddress = ($content['show_address'] ?? '1') === '1' && $address !== '';
    $showHours = ($content['show_working_hours'] ?? '1') === '1' && $hours !== '';
    $directionsUrl = $content['directions_url'] ?? '';
    if ($directionsUrl === '' && $address !== '') {
        $directionsUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address);
    }
    $showDirections = ($content['show_directions_button'] ?? '1') === '1' && $directionsUrl !== '' && !empty($content['directions_label']);
    $showContact = ($content['show_contact_button'] ?? '0') === '1' && !empty($content['contact_url']) && !empty($content['contact_label']);
    $showDetails = ($content['show_location_details'] ?? '1') === '1' && ($showAddress || $showHours || !empty($content['location_note']) || $showDirections || $showContact);
    $mapLeft = ($content['map_position'] ?? 'right') === 'left';
    $newTab = ($content['open_new_tab'] ?? '1') === '1';
@endphp
<div class="site-container site-map-managed variant-{{ $section->variant }} {{ $mapLeft ? 'map-left' : 'map-right' }} map-height-{{ $content['map_height'] ?? 'medium' }} {{ !$showMap ? 'map-empty' : '' }} {{ !$showDetails ? 'details-empty' : '' }}">
    <header class="site-map-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: 'Visit our location' }}</h2>
        @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
    </header>
    <div class="site-map-layout">
        @if ($showDetails)
            <div class="site-map-details">
                @if (!empty($content['location_heading'])) <h3>{{ $content['location_heading'] }}</h3> @endif
                @if ($showAddress)<div class="site-map-detail"><strong>{{ $content['address_label'] ?: 'Address' }}</strong><p>{{ $address }}</p></div>@endif
                @if ($showHours)<div class="site-map-detail"><strong>{{ $content['hours_label'] ?: 'Working hours' }}</strong><p>{{ $hours }}</p></div>@endif
                @if (!empty($content['location_note'])) <p class="site-map-note">{{ $content['location_note'] }}</p> @endif
                @if ($showDirections || $showContact)
                    <div class="site-map-actions">
                        @if ($showDirections)<a class="site-button site-button-accent" href="{{ $directionsUrl }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $content['directions_label'] }}</a>@endif
                        @if ($showContact)<a class="site-button" href="{{ $content['contact_url'] }}" @if ($newTab) target="_blank" rel="noopener noreferrer" @endif>{{ $content['contact_label'] }}</a>@endif
                    </div>
                @endif
            </div>
        @endif
        @if ($showMap)
            <iframe class="site-map-frame" src="{{ $embedUrl }}" title="{{ $content['map_title'] ?: 'Map showing our location' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @endif
    </div>
</div>
