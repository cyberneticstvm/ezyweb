<?php

namespace App\Publishing;

use App\Models\Website;
use App\Models\WebsiteVersion;
use Illuminate\Support\Facades\URL;

class PreviewUrlFactory
{
    public function draft(Website $website, ?string $pageSlug = null): string
    {
        return URL::temporarySignedRoute(
            'website.preview.draft',
            now()->addMinutes(30),
            array_filter(['website' => $website->getKey(), 'pageSlug' => $pageSlug]),
            absolute: false,
        );
    }

    public function version(Website $website, WebsiteVersion $version, ?string $pageSlug = null): string
    {
        return URL::temporarySignedRoute(
            'website.preview.version',
            now()->addMinutes(30),
            array_filter([
                'website' => $website->getKey(),
                'version' => $version->getKey(),
                'pageSlug' => $pageSlug,
            ]),
            absolute: false,
        );
    }
}
