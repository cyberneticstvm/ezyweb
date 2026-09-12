@php
    $settings = $contactSettings ?? [];
    $fields = is_array($settings['form_fields'] ?? null) ? $settings['form_fields'] : [];
    $showAddress = ($content['show_address'] ?? '1') === '1' && !empty($settings['address']);
    $showPhones = ($content['show_phone_numbers'] ?? '1') === '1' && !empty($settings['phone_numbers']);
    $showEmails = ($content['show_email_addresses'] ?? '1') === '1' && !empty($settings['email_addresses']);
    $showHours = ($content['show_working_hours'] ?? '1') === '1' && !empty($settings['working_hours']);
    $showMap = ($content['show_map'] ?? '1') === '1' && !empty($settings['maps_embed_url']);
    $showForm = ($content['show_form'] ?? '1') === '1';
    $hasDetails = $showAddress || $showPhones || $showEmails || $showHours || $showMap;
    $formLeft = ($content['form_position'] ?? 'right') === 'left';
@endphp
<div class="site-container site-contact-managed variant-{{ $section->variant }} {{ $formLeft ? 'form-left' : 'form-right' }} map-height-{{ $content['map_height'] ?? 'medium' }}">
    <header class="site-contact-header site-copy">
        @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
        <h2>{{ $content['heading'] ?: ($settings['heading'] ?? 'Contact us') }}</h2>
        @if (!empty($content['description'] ?: ($settings['description'] ?? null))) <p class="site-lead">{{ $content['description'] ?: $settings['description'] }}</p> @endif
    </header>
    <div class="site-contact-grid {{ !$hasDetails ? 'details-empty' : '' }} {{ !$showForm ? 'form-empty' : '' }}">
        @if ($hasDetails)
            <div class="site-contact-details">
                @if (($content['show_details_heading'] ?? '1') === '1' && !empty($content['details_heading'])) <h3>{{ $content['details_heading'] }}</h3> @endif
                <div class="site-contact-detail-list">
                    @if ($showAddress)<div class="site-contact-detail"><strong>{{ $content['address_label'] ?: 'Address' }}</strong><p>{{ $settings['address'] }}</p></div>@endif
                    @if ($showPhones)<div class="site-contact-detail"><strong>{{ $content['phone_label'] ?: 'Phone' }}</strong><p>@foreach ($settings['phone_numbers'] as $phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>@if (!$loop->last)<br>@endif @endforeach</p></div>@endif
                    @if ($showEmails)<div class="site-contact-detail"><strong>{{ $content['email_label'] ?: 'Email' }}</strong><p>@foreach ($settings['email_addresses'] as $email)<a href="mailto:{{ $email }}">{{ $email }}</a>@if (!$loop->last)<br>@endif @endforeach</p></div>@endif
                    @if ($showHours)<div class="site-contact-detail"><strong>{{ $content['hours_label'] ?: 'Working hours' }}</strong><p>{{ $settings['working_hours'] }}</p></div>@endif
                </div>
                @if ($showMap)<iframe class="site-contact-map" src="{{ $settings['maps_embed_url'] }}" title="{{ $content['map_title'] ?: 'Map showing our location' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>@endif
            </div>
        @endif
        @if ($showForm && !empty($settings['is_form_enabled']) && !$previewMode)
            <form action="/contact/enquiries" method="post" class="site-contact-form">
                @csrf
                @if (($content['show_form_heading'] ?? '1') === '1' && !empty($content['form_heading'])) <h3>{{ $content['form_heading'] }}</h3> @endif
                @if (($content['show_form_description'] ?? '1') === '1' && !empty($content['form_description'])) <p class="site-contact-form-description">{{ $content['form_description'] }}</p> @endif
                @if (session('contact_success')) <p class="site-form-success" role="status">{{ session('contact_success') }}</p> @endif
                @if ($errors->any()) <p class="site-form-error" role="alert">Please correct the highlighted fields.</p> @endif
                <label>{{ $content['name_label'] ?: 'Name' }}<input name="name" value="{{ old('name') }}" placeholder="{{ $content['name_placeholder'] ?? '' }}" required maxlength="120" autocomplete="name">@error('name')<span>{{ $message }}</span>@enderror</label>
                @if (($fields['email'] ?? true) === true)<label>{{ $content['email_field_label'] ?: 'Email' }}<input type="email" name="email" value="{{ old('email') }}" placeholder="{{ $content['email_placeholder'] ?? '' }}" maxlength="255" autocomplete="email">@error('email')<span>{{ $message }}</span>@enderror</label>@endif
                @if (($fields['phone'] ?? true) === true)<label>{{ $content['phone_field_label'] ?: 'Phone' }}<input name="phone" value="{{ old('phone') }}" placeholder="{{ $content['phone_placeholder'] ?? '' }}" maxlength="40" autocomplete="tel">@error('phone')<span>{{ $message }}</span>@enderror</label>@endif
                @if (($fields['subject'] ?? true) === true)<label>{{ $content['subject_label'] ?: 'Subject' }}<input name="subject" value="{{ old('subject') }}" placeholder="{{ $content['subject_placeholder'] ?? '' }}" maxlength="255">@error('subject')<span>{{ $message }}</span>@enderror</label>@endif
                <label>{{ $content['message_label'] ?: 'Message' }}<textarea name="message" required maxlength="5000" rows="6" placeholder="{{ $content['message_placeholder'] ?? '' }}">{{ old('message') }}</textarea>@error('message')<span>{{ $message }}</span>@enderror</label>
                <label class="site-honeypot" aria-hidden="true">Website<input name="company_website" tabindex="-1" autocomplete="off"></label>
                @if (!empty($turnstileSiteKey)) <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div> @endif
                @if (!empty($content['privacy_note'])) <p class="site-contact-privacy">{{ $content['privacy_note'] }}</p> @endif
                <button class="site-button site-button-accent" type="submit">{{ $content['submit_label'] ?: 'Send enquiry' }}</button>
            </form>
        @elseif ($showForm && $previewMode)
            <div class="site-contact-form site-contact-form-preview">@if (($content['show_form_heading'] ?? '1') === '1' && !empty($content['form_heading'])) <h3>{{ $content['form_heading'] }}</h3> @endif<p>Contact form submissions are disabled in preview mode.</p></div>
        @endif
    </div>
</div>
