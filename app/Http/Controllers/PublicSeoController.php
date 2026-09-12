<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Publishing\PublishedWebsiteCache;
use App\Seo\SeoPresenter;
use App\Seo\SeoPublicFiles;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicSeoController extends Controller
{
    public function sitemap(Request $request, CurrentTenant $context, PublishedWebsiteCache $cache, SeoPublicFiles $files): Response
    {
        $website = $this->website($context);
        $entries = $files->sitemapEntries($website, $cache->snapshot($website));

        return response()->view('tenant.sitemap', ['entries' => $entries, 'origin' => $request->getSchemeAndHttpHost()], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(Request $request, CurrentTenant $context, PublishedWebsiteCache $cache, SeoPublicFiles $files): Response
    {
        $website = $this->website($context);

        return response($files->robots($website, $cache->snapshot($website), $request->getSchemeAndHttpHost()), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function notFound(Request $request, CurrentTenant $context, PublishedWebsiteCache $cache, SeoPresenter $seo): Response
    {
        $website = $this->website($context);
        $snapshot = $cache->snapshot($website);

        return response()->view('tenant.errors.404', ['website' => $website, 'seo' => $seo->notFound($website, $snapshot, $request)], 404);
    }

    private function website(CurrentTenant $context): Website
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);

        return $website;
    }
}
