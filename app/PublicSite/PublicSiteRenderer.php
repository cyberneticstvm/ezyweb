<?php

namespace App\PublicSite;

use App\Builder\SectionRegistry;
use App\Enums\WebsiteType;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Template;
use App\Models\Website;
use App\Publishing\ContentModuleSnapshot;
use App\Publishing\ContactSettingsSnapshot;
use App\Seo\SeoPresenter;
use App\Themes\ThemeTokenService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicSiteRenderer
{
    public function __construct(
        private readonly SectionRegistry $registry,
        private readonly SectionViewResolver $views,
        private readonly ThemeTokenService $themes,
        private readonly ContentModuleSnapshot $contentModules,
        private readonly ContactSettingsSnapshot $contactSettings,
        private readonly SeoPresenter $seo,
    ) {}

    public function render(Website $website, Page $page): View
    {
        $template = Template::query()->where('key', $website->template_key)->where('is_active', true)->firstOrFail();
        $theme = $this->themes->forWebsite($website, $template);
        $sections = $page->sections()->where('is_enabled', true)->get();
        $this->normalizeSections($sections);

        $navigation = $this->navigation($website, $page, $sections);

        return view('tenant.site', [
            'website' => $website,
            'page' => $page,
            'sections' => $sections,
            'template' => $template,
            'theme' => $theme,
            'themeStyle' => $this->themeStyle($theme),
            'navigation' => $navigation,
            'contactUrl' => collect($navigation)->first(fn (array $item): bool => $item['url'] === '#contact' || $item['url'] === '/contact')['url'] ?? null,
            'sectionViews' => collect($this->registry->types())->mapWithKeys(fn (string $type): array => [$type => $this->views->view($type)])->all(),
            'previewMode' => false,
            'previewLabel' => null,
            'contentCollections' => [],
            'siteSettings' => ['contact' => [], 'whatsapp' => []],
            'turnstileSiteKey' => null,
            'seo' => ['language' => 'en', 'title' => $page->title.' · '.$website->name, 'description' => $page->title.' — '.$website->name, 'robots' => 'noindex, nofollow', 'canonical' => request()->url()],
        ]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array{preview?: bool, label?: string, url_for_page?: Closure(string|null): string}  $presentation
     */
    public function renderSnapshot(Website $source, array $snapshot, ?string $pageSlug = null, array $presentation = []): View
    {
        $websiteData = is_array($snapshot['website'] ?? null) ? $snapshot['website'] : [];
        abort_if($pageSlug !== null && ($websiteData['type'] ?? null) === WebsiteType::LandingPage->value, 404);

        $pageData = collect(is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [])->first(function (mixed $candidate) use ($pageSlug): bool {
            if (! is_array($candidate) || ($candidate['is_enabled'] ?? false) !== true) {
                return false;
            }

            return $pageSlug === null
                ? ($candidate['is_home'] ?? false) === true
                : ($candidate['slug'] ?? null) === $pageSlug;
        });
        abort_unless(is_array($pageData), 404);

        $website = new Website;
        $website->forceFill([
            ...$websiteData,
            'id' => $source->getKey(),
            'tenant_id' => $source->tenant_id,
            'status' => $source->status,
        ]);
        $website->exists = true;
        $page = new Page;
        $page->forceFill($pageData);
        $page->exists = true;
        $sections = collect(is_array($pageData['sections'] ?? null) ? $pageData['sections'] : [])
            ->filter(fn (mixed $section): bool => is_array($section) && ($section['is_enabled'] ?? false) === true)
            ->map(function (array $data): PageSection {
                $section = new PageSection;
                $section->forceFill($data);
                $section->exists = true;

                return $section;
            })->values();
        $this->normalizeSections($sections);

        $template = Template::query()->where('key', $website->template_key)->where('is_active', true)->firstOrFail();
        $theme = $this->themes->forWebsite($website, $template);
        $navigation = $this->snapshotNavigation(
            $website,
            $page,
            $sections,
            is_array($snapshot['pages'] ?? null) ? $snapshot['pages'] : [],
            $presentation['url_for_page'] ?? null,
        );

        return view('tenant.site', [
            'website' => $website,
            'page' => $page,
            'sections' => $sections,
            'template' => $template,
            'theme' => $theme,
            'themeStyle' => $this->themeStyle($theme),
            'navigation' => $navigation,
            'contactUrl' => collect($navigation)->first(fn (array $item): bool => $item['url'] === '#contact' || str_ends_with($item['url'], '/contact'))['url'] ?? null,
            'sectionViews' => collect($this->registry->types())->mapWithKeys(fn (string $type): array => [$type => $this->views->view($type)])->all(),
            'previewMode' => $presentation['preview'] ?? false,
            'previewLabel' => $presentation['label'] ?? null,
            'contentCollections' => $this->contentModules->publicCollections($snapshot),
            'siteSettings' => $this->contactSettings->publicSettings($snapshot, $pageSlug),
            'turnstileSiteKey' => config('services.turnstile.site_key'),
            'seo' => $this->seo->page($source, $snapshot, $pageData, request(), $presentation['preview'] ?? false),
        ]);
    }

    /** @param Collection<int, PageSection> $sections */
    private function normalizeSections(Collection $sections): void
    {
        foreach ($sections as $section) {
            $definition = $this->registry->get($section->type);
            $content = $section->getAttribute('content');
            $design = $section->getAttribute('design_settings');
            $variant = (string) $section->getAttribute('variant');
            $anchor = $section->getAttribute('anchor');

            $section->setAttribute('content', $this->registry->normalizeContent($section->type, is_array($content) ? $content : []));
            $section->setAttribute('design_settings', $this->registry->normalizeDesign(is_array($design) ? $design : []));
            $section->setAttribute('variant', in_array($variant, $this->registry->variants($section->type), true)
                ? $variant
                : ($definition['variants'][0]['key'] ?? 'default'));
            $section->setAttribute('anchor', is_string($anchor) && $anchor !== '' ? Str::slug($anchor) : null);
        }
    }

    /**
     * @param  Collection<int, PageSection>  $sections
     * @return list<array{label: string, url: string, current: bool}>
     */
    private function navigation(Website $website, Page $currentPage, Collection $sections): array
    {
        if ($website->getAttribute('type') === WebsiteType::LandingPage) {
            return $sections
                ->filter(fn (PageSection $section): bool => $section->anchor !== null)
                ->map(fn (PageSection $section): array => [
                    'label' => $this->registry->get($section->type)['label'] ?? Str::headline($section->type),
                    'url' => '#'.$section->anchor,
                    'current' => false,
                ])->values()->all();
        }

        return $website->pages()->where('is_enabled', true)->get()
            ->map(fn (Page $page): array => [
                'label' => $page->navigation_label ?: $page->title,
                'url' => $page->is_home ? '/' : '/'.$page->slug,
                'current' => $page->is($currentPage),
            ])->values()->all();
    }

    /**
     * @param  Collection<int, PageSection>  $sections
     * @param  list<mixed>  $pages
     * @param  Closure(string|null): string|null  $urlForPage
     * @return list<array{label: string, url: string, current: bool}>
     */
    private function snapshotNavigation(Website $website, Page $currentPage, Collection $sections, array $pages, ?Closure $urlForPage): array
    {
        if ($website->getAttribute('type') === WebsiteType::LandingPage) {
            return $sections->filter(fn (PageSection $section): bool => $section->anchor !== null)
                ->map(fn (PageSection $section): array => [
                    'label' => $this->registry->get($section->type)['label'] ?? Str::headline($section->type),
                    'url' => '#'.$section->anchor,
                    'current' => false,
                ])->values()->all();
        }

        return collect($pages)->filter(fn (mixed $page): bool => is_array($page) && ($page['is_enabled'] ?? false) === true)
            ->map(function (array $page) use ($currentPage, $urlForPage): array {
                $slug = ($page['is_home'] ?? false) === true ? null : (string) $page['slug'];

                return [
                    'label' => (string) ($page['navigation_label'] ?: $page['title']),
                    'url' => $urlForPage ? $urlForPage($slug) : ($slug === null ? '/' : '/'.$slug),
                    'current' => ($currentPage->is_home && $slug === null) || $currentPage->slug === $slug,
                ];
            })->values()->all();
    }

    /** @param array<string, string> $theme */
    private function themeStyle(array $theme): string
    {
        return collect($this->themes->cssVariables($theme))
            ->map(fn (string $value, string $property): string => $property.': '.$value)
            ->implode('; ');
    }
}
