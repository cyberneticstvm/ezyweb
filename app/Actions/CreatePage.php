<?php

namespace App\Actions;

use App\Builder\DraftState;
use App\Enums\WebsiteType;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Subscriptions\FeatureGate;

class CreatePage
{
    public function __construct(private readonly DraftState $drafts, private readonly FeatureGate $features) {}

    /** @param array{title: string, slug?: string|null, navigation_label?: string|null} $data */
    public function execute(Website $website, array $data): Page
    {
        return DB::transaction(function () use ($website, $data): Page {
            $locked = Website::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($website->getKey());
            $existingCount = Page::query()->where('website_id', $locked->getKey())->count();
            $this->features->ensureWithinLimit($locked, 'pages_limit', $existingCount, 'pages');
            if ($locked->getAttribute('type') === WebsiteType::LandingPage && $existingCount > 0) {
                throw ValidationException::withMessages(['title' => 'Landing websites can contain only the home page.']);
            }

            $slug = Str::slug(($data['slug'] ?? null) ?: $data['title']) ?: 'page';
            if (in_array($slug, config('public_site.reserved_page_slugs'), true)) {
                throw ValidationException::withMessages(['slug' => 'This page URL is reserved by Ezyweb.']);
            }
            if (Page::query()->where('website_id', $locked->getKey())->where('slug', $slug)->exists()) {
                throw ValidationException::withMessages(['slug' => 'This page URL is already in use.']);
            }

            $page = Page::query()->create([
                'tenant_id' => $locked->tenant_id,
                'website_id' => $locked->getKey(),
                'title' => trim($data['title']),
                'slug' => $slug,
                'navigation_label' => trim(($data['navigation_label'] ?? null) ?: $data['title']),
                'is_home' => $existingCount === 0,
                'is_enabled' => true,
                'sort_order' => $existingCount,
            ]);
            $this->drafts->touch($locked);

            return $page;
        });
    }
}
