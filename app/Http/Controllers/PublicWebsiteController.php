<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\PublicSite\PublicSiteRenderer;
use App\Publishing\PublishedWebsiteCache;
use App\Tenancy\CurrentTenant;
use App\Seo\SeoPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicWebsiteController extends Controller
{
    public function home(CurrentTenant $context, PublicSiteRenderer $renderer, PublishedWebsiteCache $cache): View|InertiaResponse
    {
        $website = $context->website();
        if (! $website instanceof Website) {
            return Inertia::render('home');
        }

        return $renderer->renderSnapshot($website, $cache->snapshot($website));
    }

    public function show(string $pageSlug, Request $request, CurrentTenant $context, PublicSiteRenderer $renderer, PublishedWebsiteCache $cache, SeoPresenter $seo): View|Response
    {
        $website = $context->website();
        abort_unless($website instanceof Website, 404);

        $snapshot = $cache->snapshot($website);
        $exists = collect(is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [])->contains(fn (mixed $page): bool => is_array($page) && ($page['slug'] ?? null) === $pageSlug && ($page['is_enabled'] ?? false) === true);
        if (! $exists) {
            return response()->view('tenant.errors.404', ['website' => $website, 'seo' => $seo->notFound($website, $snapshot, $request)], 404);
        }

        return $renderer->renderSnapshot($website, $snapshot, $pageSlug);
    }
}
