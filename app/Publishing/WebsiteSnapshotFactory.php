<?php

namespace App\Publishing;

use App\Enums\WebsiteType;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use App\Tenancy\TenantScope;

class WebsiteSnapshotFactory
{
    public function __construct(private readonly ContentModuleSnapshot $contentModules, private readonly ContactSettingsSnapshot $contactSettings, private readonly SeoSnapshot $seo) {}

    /** @return array<string, mixed> */
    public function capture(Website $website): array
    {
        $pages = Page::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())
            ->orderBy('sort_order')->orderBy('id')->get();
        $sections = PageSection::query()->withoutGlobalScope(TenantScope::class)
            ->where('website_id', $website->getKey())
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('page_id');

        return [
            'schema_version' => 1,
            'website' => [
                'name' => $website->name,
                'slug' => $website->slug,
                'type' => $website->getAttribute('type') instanceof WebsiteType
                    ? $website->getAttribute('type')->value
                    : (string) $website->getAttribute('type'),
                'template_key' => $website->template_key,
                'theme_settings' => $website->theme_settings,
                'initial_sections' => $website->initial_sections,
            ],
            'settings' => $this->contactSettings->capture($website),
            'navigation' => [],
            'global_sections' => [],
            'content_modules' => $this->contentModules->capture($website),
            'seo' => $this->seo->capture($website),
            'pages' => $pages->map(fn (Page $page): array => [
                'title' => $page->title,
                'slug' => $page->slug,
                'navigation_label' => $page->navigation_label,
                'is_home' => $page->is_home,
                'is_enabled' => $page->is_enabled,
                'sort_order' => $page->sort_order,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'sections' => $sections->get($page->getKey(), collect())->map(fn (PageSection $section): array => [
                    'type' => $section->type,
                    'variant' => $section->variant,
                    'anchor' => $section->anchor,
                    'content' => $section->content,
                    'design_settings' => $section->design_settings,
                    'is_enabled' => $section->is_enabled,
                    'show_on_desktop' => $section->show_on_desktop,
                    'show_on_tablet' => $section->show_on_tablet,
                    'show_on_mobile' => $section->show_on_mobile,
                    'sort_order' => $section->sort_order,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
