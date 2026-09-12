<?php

namespace App\Http\Controllers;

use App\Builder\BuilderWorkspace;
use App\Http\Requests\UpdatePageSeoRequest;
use App\Http\Requests\UpdateSiteSeoRequest;
use App\Models\Page;
use App\Models\Redirect;
use App\Seo\SeoAudit;
use App\Seo\SeoManager;
use App\Tenancy\TenantScope;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SeoController extends Controller
{
    public function index(BuilderWorkspace $workspace, SeoManager $manager, SeoAudit $audit): Response
    {
        $website = $workspace->website();
        $this->authorize('view', $website);
        $site = $manager->site($website);
        $siteSettings = $site->toArray();
        foreach (['site_title', 'default_meta_description', 'default_social_image', 'business_name', 'primary_language', 'google_site_verification', 'google_analytics_id', 'not_found_title', 'not_found_message', 'not_found_button_label'] as $field) {
            $siteSettings[$field] = is_string($siteSettings[$field] ?? null) ? $siteSettings[$field] : '';
        }
        $pages = Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->orderBy('sort_order')->get()->map(function (Page $page) use ($manager): array {
            return ['id' => $page->getKey(), 'title' => $page->title, 'slug' => $page->slug, 'is_home' => $page->is_home, 'is_enabled' => $page->is_enabled, 'seo' => $manager->page($page)->toArray()];
        });
        $redirects = Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->latest()->get();

        return Inertia::render('seo/index', [
            'siteSettings' => [...$siteSettings, 'social_profiles_text' => implode("\n", $site->social_profiles ?? [])],
            'pages' => $pages, 'redirects' => $redirects, 'audit' => $audit->run($website),
        ]);
    }

    public function updateSite(UpdateSiteSeoRequest $request, BuilderWorkspace $workspace, SeoManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->updateSite($website, $request->validated());

        return back()->with('success', 'Site SEO settings saved.');
    }

    public function updatePage(int $page, UpdatePageSeoRequest $request, BuilderWorkspace $workspace, SeoManager $manager): RedirectResponse
    {
        $website = $workspace->website();
        $this->authorize('update', $website);
        $manager->updatePage($website, $workspace->page($page), $request->validated());

        return back()->with('success', 'Page SEO settings saved.');
    }
}
