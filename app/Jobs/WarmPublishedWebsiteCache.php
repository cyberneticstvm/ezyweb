<?php

namespace App\Jobs;

use App\Models\Website;
use App\Publishing\PublishedWebsiteCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WarmPublishedWebsiteCache implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $websiteId) {}

    public function handle(PublishedWebsiteCache $cache): void
    {
        $website = Website::query()->withoutGlobalScopes()->find($this->websiteId);
        if ($website?->published_version_id) {
            $cache->snapshot($website);
        }
    }
}
