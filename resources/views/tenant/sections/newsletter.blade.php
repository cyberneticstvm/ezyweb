@php
    $showName = ($content['show_name_field'] ?? '0') === '1';
    $privacyNewTab = ($content['privacy_new_tab'] ?? '0') === '1';
@endphp
<div class="site-container site-newsletter-managed variant-{{ $section->variant }} form-width-{{ $content['form_width'] ?? 'medium' }} align-form-{{ $content['form_alignment'] ?? 'left' }}">
    <div class="site-newsletter-shell">
        <div class="site-newsletter-copy site-copy">
            @if (!empty($content['eyebrow'])) <p class="site-eyebrow">{{ $content['eyebrow'] }}</p> @endif
            <h2>{{ $content['heading'] ?: 'Join our newsletter' }}</h2>
            @if (!empty($content['description'])) <p class="site-lead">{{ $content['description'] }}</p> @endif
        </div>
        <div class="site-newsletter-form-wrap">
            @if (session('newsletter_success'))
                <p class="site-newsletter-success" role="status">{{ $content['success_message'] ?: 'Thank you for subscribing.' }}</p>
            @elseif (!$previewMode)
                <form action="/newsletter/subscriptions" method="post" class="site-newsletter-form">
                    @csrf
                    @if (($content['show_form_heading'] ?? '0') === '1' && !empty($content['form_heading'])) <h3>{{ $content['form_heading'] }}</h3> @endif
                    @if (($content['show_form_description'] ?? '0') === '1' && !empty($content['form_description'])) <p class="site-newsletter-form-description">{{ $content['form_description'] }}</p> @endif
                    @if ($errors->newsletter->has('email') || $errors->newsletter->has('consent')) <p class="site-form-error" role="alert">Please check your email and consent, then try again.</p> @endif
                    <div class="site-newsletter-fields">
                        @if ($showName)<label><span>{{ $content['name_label'] ?: 'Name' }}</span><input name="name" value="{{ old('name') }}" placeholder="{{ $content['name_placeholder'] ?? '' }}" maxlength="120" autocomplete="name">@error('name', 'newsletter')<small>{{ $message }}</small>@enderror</label>@endif
                        <label><span>{{ $content['email_field_label'] ?: 'Email address' }}</span><input type="email" name="email" value="{{ old('email') }}" placeholder="{{ $content['email_placeholder'] ?? '' }}" required maxlength="255" autocomplete="email">@error('email', 'newsletter')<small>{{ $message }}</small>@enderror</label>
                        <button class="site-button site-button-accent" type="submit">{{ $content['button_label'] ?: 'Subscribe' }}</button>
                    </div>
                    <label class="site-newsletter-consent"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>{{ $content['consent_label'] ?: 'I agree to receive newsletter emails and can unsubscribe at any time.' }} @if (!empty($content['privacy_label']) && !empty($content['privacy_url']))<a href="{{ $content['privacy_url'] }}" @if ($privacyNewTab) target="_blank" rel="noopener noreferrer" @endif>{{ $content['privacy_label'] }}</a>@endif</span></label>
                    @error('consent', 'newsletter')<small class="site-form-error">{{ $message }}</small>@enderror
                    <label class="site-honeypot" aria-hidden="true">Website<input name="company_website" tabindex="-1" autocomplete="off"></label>
                    @if (($content['show_privacy_note'] ?? '1') === '1' && !empty($content['privacy_note'])) <p class="site-newsletter-note">{{ $content['privacy_note'] }}</p> @endif
                </form>
            @else
                <div class="site-newsletter-form site-newsletter-preview">
                    <p>Newsletter signup is disabled in preview mode.</p>
                </div>
            @endif
        </div>
    </div>
</div>
