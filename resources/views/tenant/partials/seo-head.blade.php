<meta name="description" content="{{ $seo['description'] ?? '' }}">
<meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
<link rel="canonical" href="{{ $seo['canonical'] ?? request()->url() }}">
<meta property="og:type" content="{{ $seo['openGraphType'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['openGraphTitle'] ?? $seo['title'] ?? '' }}">
<meta property="og:description" content="{{ $seo['openGraphDescription'] ?? $seo['description'] ?? '' }}">
<meta property="og:url" content="{{ $seo['canonical'] ?? request()->url() }}">
@if (!empty($seo['image']))<meta property="og:image" content="{{ $seo['image'] }}">@endif
<meta name="twitter:card" content="{{ !empty($seo['image']) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['openGraphTitle'] ?? $seo['title'] ?? '' }}">
<meta name="twitter:description" content="{{ $seo['openGraphDescription'] ?? $seo['description'] ?? '' }}">
@if (!empty($seo['image']))<meta name="twitter:image" content="{{ $seo['image'] }}">@endif
@if (!empty($seo['googleVerification']))<meta name="google-site-verification" content="{{ $seo['googleVerification'] }}">@endif
@if (!empty($seo['jsonLd']))<script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! $seo['jsonLd'] !!}</script>@endif
@if (!empty($seo['analyticsId']))
<script nonce="{{ Vite::cspNonce() }}" async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($seo['analyticsId']) }}"></script>
<script nonce="{{ Vite::cspNonce() }}">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date);gtag('config',@json($seo['analyticsId']),{'anonymize_ip':true});</script>
@endif
