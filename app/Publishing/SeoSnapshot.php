<?php

namespace App\Publishing;

use App\Models\Page;
use App\Models\PageSeoSetting;
use App\Models\Redirect;
use App\Models\Website;
use App\Models\WebsiteSeoSetting;
use App\Seo\SeoManager;
use App\Tenancy\TenantScope;
use Illuminate\Support\Arr;

class SeoSnapshot
{
    public function __construct(private readonly SeoManager $manager) {}

    /** @return array{site: array<string, mixed>, pages: array<string, array<string, mixed>>, redirects: list<array<string, mixed>>} */
    public function capture(Website $website): array
    {
        $site = $this->manager->site($website);
        $pages = Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->with('seoSetting')->get();
        $redirects = Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->where('is_active', true)->get();

        return [
            'site' => Arr::except($site->attributesToArray(), ['id', 'tenant_id', 'website_id', 'created_at', 'updated_at']),
            'pages' => $pages->mapWithKeys(function (Page $page): array {
                $seo = $page->seoSetting ?? $this->manager->page($page);

                return [$page->slug => Arr::except($seo->attributesToArray(), ['id', 'tenant_id', 'website_id', 'page_id', 'created_at', 'updated_at'])];
            })->all(),
            'redirects' => $redirects->map(fn (Redirect $redirect): array => Arr::only($redirect->attributesToArray(), ['public_id', 'source_path', 'target_path', 'status_code']))->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function data(array $snapshot): array
    {
        return $this->associative($snapshot['seo'] ?? null);
    }

    /** @param array<string, mixed> $seo */
    public function restore(Website $website, array $seo): void
    {
        $site = $this->associative($seo['site'] ?? null);
        if ($site !== []) {
            WebsiteSeoSetting::query()->withoutGlobalScope(TenantScope::class)->updateOrCreate(
                ['website_id' => $website->getKey()],
                ['tenant_id' => $website->tenant_id, ...Arr::only($site, (new WebsiteSeoSetting)->getFillable())],
            );
        }
        $pageSettings = $this->associative($seo['pages'] ?? null);
        $pages = Page::query()->withoutGlobalScopes()->where('website_id', $website->getKey())->get()->keyBy('slug');
        foreach ($pageSettings as $slug => $values) {
            $page = $pages->get($slug);
            if (! $page instanceof Page || ! is_array($values)) {
                continue;
            }
            PageSeoSetting::query()->withoutGlobalScope(TenantScope::class)->updateOrCreate(
                ['page_id' => $page->getKey()],
                ['tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), ...Arr::only($values, (new PageSeoSetting)->getFillable())],
            );
        }
        Redirect::query()->withoutGlobalScope(TenantScope::class)->where('website_id', $website->getKey())->delete();
        foreach (is_array($seo['redirects'] ?? null) ? $seo['redirects'] : [] as $redirect) {
            if (is_array($redirect)) {
                Redirect::query()->withoutGlobalScope(TenantScope::class)->create([
                    'tenant_id' => $website->tenant_id, 'website_id' => $website->getKey(), 'is_active' => true,
                    ...Arr::only($redirect, ['public_id', 'source_path', 'target_path', 'status_code']),
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function associative(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }
        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
