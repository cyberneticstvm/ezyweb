@php
    $visibility = implode(' ', array_filter([
        ($settings['show_on_desktop'] ?? true) ? null : 'site-hide-desktop',
        ($settings['show_on_tablet'] ?? true) ? null : 'site-hide-tablet',
        ($settings['show_on_mobile'] ?? true) ? null : 'site-hide-mobile',
    ]));
@endphp
@if (!empty($settings['is_enabled']) && !empty($settings['url']))
    <a href="{{ $settings['url'] }}" class="site-whatsapp site-whatsapp-{{ $settings['position'] ?? 'right' }} {{ $visibility }} {{ !empty($settings['pulse_animation']) ? 'site-whatsapp-pulse' : '' }}" style="--whatsapp-bottom: {{ (int) ($settings['bottom_offset'] ?? 24) }}px; --whatsapp-delay: {{ (int) ($settings['display_delay'] ?? 0) }}s" rel="noopener noreferrer" aria-label="Chat with us on WhatsApp">
        <span aria-hidden="true">WA</span>@if (!empty($settings['show_label'])) <strong>WhatsApp</strong> @endif
    </a>
@endif
@if (!empty($settings['mobile_sticky_bar']))
    <nav class="site-mobile-contact-bar" aria-label="Quick contact">
        @if (!empty($settings['call_number'])) <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['call_number']) }}">Call</a> @endif
        @if (!empty($settings['url'])) <a href="{{ $settings['url'] }}" rel="noopener noreferrer">WhatsApp</a> @endif
        @if (!empty($settings['directions_url'])) <a href="{{ $settings['directions_url'] }}" rel="noopener noreferrer">Directions</a> @endif
        <a href="#contact">Enquiry</a>
    </nav>
@endif
