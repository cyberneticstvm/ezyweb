<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($entries as $entry)
    <url><loc>{{ str_starts_with($entry['location'], 'http://') || str_starts_with($entry['location'], 'https://') ? $entry['location'] : $origin.$entry['location'] }}</loc>@if (!empty($entry['lastmod']))<lastmod>{{ $entry['lastmod'] }}</lastmod>@endif</url>
@endforeach
</urlset>
