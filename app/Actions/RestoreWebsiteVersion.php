<?php

namespace App\Actions;

use App\Enums\PublicationStatus;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVersion;
use App\Publishing\ContentModuleSnapshot;
use App\Publishing\ContactSettingsSnapshot;
use App\Publishing\SeoSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Subscriptions\VersionHistoryLimit;

class RestoreWebsiteVersion
{
    public function __construct(private readonly ContentModuleSnapshot $contentModules, private readonly ContactSettingsSnapshot $contactSettings, private readonly SeoSnapshot $seo, private readonly VersionHistoryLimit $history) {}

    public function execute(Website $website, WebsiteVersion $source, User $actor): WebsiteVersion
    {
        abort_unless((int) $source->website_id === (int) $website->getKey(), 404);

        $version = DB::transaction(function () use ($website, $source, $actor): WebsiteVersion {
            $locked = Website::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($website->getKey());
            $snapshot = $source->snapshot();
            $websiteData = $snapshot['website'] ?? null;
            $pages = $snapshot['pages'] ?? null;
            if (! is_array($websiteData) || ! is_array($pages)) {
                throw ValidationException::withMessages(['version' => 'This version snapshot cannot be restored.']);
            }

            PageSection::query()->withoutGlobalScopes()->where('website_id', $locked->getKey())->forceDelete();
            Page::query()->withoutGlobalScopes()->where('website_id', $locked->getKey())->forceDelete();
            foreach ($pages as $pageData) {
                if (! is_array($pageData)) {
                    continue;
                }
                $page = Page::query()->withoutGlobalScopes()->create([
                    'tenant_id' => $locked->tenant_id,
                    'website_id' => $locked->getKey(),
                    'title' => $pageData['title'],
                    'slug' => $pageData['slug'],
                    'navigation_label' => $pageData['navigation_label'] ?? null,
                    'is_home' => $pageData['is_home'] ?? false,
                    'is_enabled' => $pageData['is_enabled'] ?? true,
                    'sort_order' => $pageData['sort_order'] ?? 0,
                ]);
                foreach (is_array($pageData['sections'] ?? null) ? $pageData['sections'] : [] as $sectionData) {
                    if (! is_array($sectionData)) {
                        continue;
                    }
                    PageSection::query()->withoutGlobalScopes()->create([
                        'tenant_id' => $locked->tenant_id,
                        'website_id' => $locked->getKey(),
                        'page_id' => $page->getKey(),
                        ...collect($sectionData)->only(['type', 'variant', 'anchor', 'content', 'design_settings', 'is_enabled', 'show_on_desktop', 'show_on_tablet', 'show_on_mobile', 'sort_order'])->all(),
                    ]);
                }
            }

            $modules = $snapshot['content_modules'] ?? [];
            if (is_array($modules)) {
                $this->contentModules->restore($locked, $modules);
            }
            $settings = $snapshot['settings'] ?? [];
            if (is_array($settings)) {
                $this->contactSettings->restore($locked, $settings);
            }
            $seo = $snapshot['seo'] ?? [];
            if (is_array($seo)) {
                $this->seo->restore($locked, $seo);
            }

            $locked->update([
                'name' => $websiteData['name'],
                'slug' => $websiteData['slug'],
                'type' => $websiteData['type'],
                'template_key' => $websiteData['template_key'],
                'theme_settings' => $websiteData['theme_settings'] ?? [],
                'initial_sections' => $websiteData['initial_sections'] ?? [],
                'draft_revision' => $locked->draft_revision + 1,
                'draft_updated_at' => now(),
                'publication_status' => PublicationStatus::Saved,
                'last_publish_error' => null,
            ]);

            $number = ((int) WebsiteVersion::query()->withoutGlobalScopes()->where('website_id', $locked->getKey())->max('version_number')) + 1;

            return WebsiteVersion::query()->withoutGlobalScopes()->create([
                'tenant_id' => $locked->tenant_id,
                'website_id' => $locked->getKey(),
                'version_number' => $number,
                'reason' => 'restore-version-'.$source->version_number,
                'snapshot_json' => $snapshot,
                'created_by' => $actor->getKey(),
                'published_at' => null,
            ]);
        }, 3);
        $this->history->prune($website->fresh());

        return $version;
    }
}
