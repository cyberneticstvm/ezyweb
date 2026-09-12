<?php

namespace App\Publishing;

use App\Models\Website;
use App\Models\WebsiteVersion;
use Illuminate\Support\Facades\Cache;

class PublishedWebsiteCache
{
    /** @return array<string, mixed> */
    public function snapshot(Website $website): array
    {
        abort_unless((bool) $website->published_version_id, 404);

        return Cache::remember(
            $this->snapshotKey($website->getKey()),
            now()->addDay(),
            function () use ($website): array {
                $version = WebsiteVersion::query()->withoutGlobalScopes()
                    ->where('website_id', $website->getKey())
                    ->findOrFail($website->published_version_id);

                return $version->snapshot();
            },
        );
    }

    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>|null  $next
     */
    public function forget(Website $website, ?array $previous = null, ?array $next = null): void
    {
        $keys = [
            $this->snapshotKey($website->getKey()),
            "website:{$website->getKey()}:settings",
            "website:{$website->getKey()}:navigation",
            "website:{$website->getKey()}:sitemap",
            "website:{$website->getKey()}:robots",
        ];

        foreach ([$previous, $next] as $snapshot) {
            foreach (is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [] as $page) {
                if (is_array($page) && is_string($page['slug'] ?? null)) {
                    $keys[] = "website:{$website->getKey()}:page:{$page['slug']}:published";
                }
            }
        }

        foreach (array_unique($keys) as $key) {
            Cache::forget($key);
        }
    }

    private function snapshotKey(int|string $websiteId): string
    {
        return "website:{$websiteId}:published";
    }
}
