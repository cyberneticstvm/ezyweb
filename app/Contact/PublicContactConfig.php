<?php

namespace App\Contact;

use App\Models\Website;
use App\Publishing\ContactSettingsSnapshot;
use App\Publishing\PublishedWebsiteCache;

class PublicContactConfig
{
    public function __construct(private readonly PublishedWebsiteCache $cache, private readonly ContactSettingsSnapshot $settings) {}

    /** @return array<string, mixed> */
    public function forWebsite(Website $website): array
    {
        return $this->settings->privateContact($this->cache->snapshot($website));
    }
}
