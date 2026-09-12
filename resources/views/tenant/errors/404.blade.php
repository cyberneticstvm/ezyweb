<!DOCTYPE html>
<html lang="{{ $seo['language'] ?? 'en' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="{{ $seo['robots'] }}"><link rel="canonical" href="{{ $seo['canonical'] }}">
    <title>{{ $seo['title'] }} · {{ $seo['siteTitle'] }}</title>@vite('resources/css/app.css')
</head>
<body class="tenant-site"><a href="#main-content" class="site-skip-link">Skip to content</a>
<main id="main-content" class="site-container py-24 text-center"><p class="site-eyebrow">404</p><h1>{{ $seo['title'] }}</h1><p class="site-lead mx-auto mt-4">{{ $seo['message'] }}</p><a href="/" class="site-button mt-8">{{ $seo['button'] }}</a></main>
</body></html>
