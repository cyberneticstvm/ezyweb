<?php

namespace App\Seo;

use App\Models\Website;
use App\Publishing\ContentModuleSnapshot;
use App\Publishing\SeoSnapshot;
use Illuminate\Support\Facades\Cache;

class SeoPublicFiles
{
    public function __construct(private readonly SeoSnapshot $seo, private readonly ContentModuleSnapshot $content) {}

    /**
     * @param array<string, mixed> $snapshot
     * @return list<array{location: string, lastmod: string|null}>
     */
    public function sitemapEntries(Website $website, array $snapshot): array
    {
        return Cache::remember("website:{$website->getKey()}:sitemap", now()->addDay(), function () use ($snapshot): array {
            $seo = $this->seo->data($snapshot);
            $site = is_array($seo['site'] ?? null) ? $seo['site'] : [];
            if (($site['search_engine_visibility'] ?? true) !== true) {
                return [];
            }
            $pageSeo = is_array($seo['pages'] ?? null) ? $seo['pages'] : [];
            $entries = collect(is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [])->filter(function (mixed $page) use ($pageSeo): bool {
                if (! is_array($page) || ($page['is_enabled'] ?? false) !== true) return false;
                $settings = is_array($pageSeo[$page['slug'] ?? ''] ?? null) ? $pageSeo[$page['slug']] : [];

                return ($settings['is_indexed'] ?? true) === true;
            })->map(function (array $page) use ($pageSeo): array {
                $settings = is_array($pageSeo[$page['slug'] ?? ''] ?? null) ? $pageSeo[$page['slug']] : [];

                return [
                    'location' => is_string($settings['canonical_url'] ?? null) && $settings['canonical_url'] !== ''
                        ? $settings['canonical_url']
                        : (($page['is_home'] ?? false) === true ? '/' : '/'.$page['slug']),
                    'lastmod' => is_string($page['updated_at'] ?? null) ? $page['updated_at'] : null,
                ];
            });
            foreach (['blog', 'news', 'events', 'careers'] as $type) {
                $records = $this->content->publicRecords($snapshot, $type);
                if ($records->isNotEmpty()) $entries->push(['location' => '/'.$type, 'lastmod' => null]);
                foreach ($records as $item) {
                    if (is_string($item['slug'] ?? null)) $entries->push(['location' => '/'.$type.'/'.$item['slug'], 'lastmod' => is_string($item['updated_at'] ?? null) ? $item['updated_at'] : null]);
                }
            }

            return $entries->unique('location')->values()->all();
        });
    }

    /** @param array<string, mixed> $snapshot */
    public function robots(Website $website, array $snapshot, string $origin): string
    {
        $visible = Cache::remember("website:{$website->getKey()}:robots", now()->addDay(), function () use ($snapshot): bool {
            $seo = $this->seo->data($snapshot);
            $site = is_array($seo['site'] ?? null) ? $seo['site'] : [];
            return ($site['search_engine_visibility'] ?? true) === true;
        });

        return $visible
            ? "User-agent: *\nAllow: /\n\nSitemap: {$origin}/sitemap.xml\n"
            : "User-agent: *\nDisallow: /\n";
    }
}
