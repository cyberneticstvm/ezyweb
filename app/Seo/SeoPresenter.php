<?php

namespace App\Seo;

use App\Models\Website;
use App\Publishing\SeoSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeoPresenter
{
    public function __construct(private readonly SeoSnapshot $snapshots) {}

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function page(Website $website, array $snapshot, array $page, Request $request, bool $preview = false): array
    {
        $seo = $this->snapshots->data($snapshot);
        $site = $this->map($seo['site'] ?? null);
        $pages = $this->map($seo['pages'] ?? null);
        $pageSeo = $this->map($pages[(string) ($page['slug'] ?? '')] ?? null);
        $siteTitle = $this->text($site['site_title'] ?? null) ?? $website->name;
        $title = $this->text($pageSeo['seo_title'] ?? null) ?? (string) ($page['title'] ?? $siteTitle);
        $description = $this->text($pageSeo['meta_description'] ?? null) ?? $this->text($site['default_meta_description'] ?? null) ?? '';
        $current = $this->currentUrl($request);
        $canonical = ! $preview && is_string($pageSeo['canonical_url'] ?? null) ? $pageSeo['canonical_url'] : $current;
        $visible = ($site['search_engine_visibility'] ?? true) === true;
        $indexed = ($pageSeo['is_indexed'] ?? true) === true;
        $followed = ($pageSeo['is_followed'] ?? true) === true;
        $robots = $preview || ! $visible ? 'noindex, nofollow, noarchive' : ($indexed ? 'index' : 'noindex').', '.($followed ? 'follow' : 'nofollow');
        $image = $this->text($pageSeo['open_graph_image'] ?? null) ?? $this->text($site['default_social_image'] ?? null);
        $schemas = $this->pageSchemas($website, $snapshot, $page, $site, $pageSeo, $canonical, $title, $description, $request);

        return $this->payload($site, $title, $description, $canonical, $robots, $image, $pageSeo, $schemas, $preview);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function archive(Website $website, array $snapshot, string $title, Request $request): array
    {
        $seo = $this->snapshots->data($snapshot);
        $site = $this->map($seo['site'] ?? null);
        $siteTitle = $this->text($site['site_title'] ?? null) ?? $website->name;
        $pageTitle = $title.' · '.$siteTitle;
        $description = $title.' published by '.$website->name.'.';
        $canonical = $this->currentUrl($request, ['page']);
        $schema = [['@context' => 'https://schema.org', '@type' => 'CollectionPage', '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $pageTitle, 'description' => $description]];

        return $this->payload($site, $pageTitle, $description, $canonical, $this->robots($site), $this->text($site['default_social_image'] ?? null), [], $schema, false);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function content(Website $website, array $snapshot, string $type, array $item, Request $request): array
    {
        $seo = $this->snapshots->data($snapshot);
        $site = $this->map($seo['site'] ?? null);
        $title = $this->text($item['seo_title'] ?? null) ?? (string) ($item['title'] ?? 'Content');
        $description = $this->text($item['seo_description'] ?? null) ?? $this->text($item['excerpt'] ?? $item['summary'] ?? null) ?? '';
        $canonical = $this->currentUrl($request);
        $image = $this->text($item['open_graph_image'] ?? null) ?? $this->text($item['featured_image'] ?? null) ?? $this->text($site['default_social_image'] ?? null);
        $schema = [$this->contentSchema($website, $site, $type, $item, $canonical, $title, $description, $image)];
        $schema[] = $this->breadcrumbs($request->getSchemeAndHttpHost(), [['name' => Str::headline($type), 'url' => $request->getSchemeAndHttpHost().'/'.$type], ['name' => (string) ($item['title'] ?? $title), 'url' => $canonical]]);

        $payload = $this->payload($site, $title, $description, $canonical, $this->robots($site), $image, [], $schema, false);
        $payload['openGraphType'] = in_array($type, ['blog', 'news'], true) ? 'article' : 'website';

        return $payload;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function notFound(Website $website, array $snapshot, Request $request): array
    {
        $seo = $this->snapshots->data($snapshot);
        $site = $this->map($seo['site'] ?? null);

        return [
            'language' => $this->text($site['primary_language'] ?? null) ?? 'en',
            'title' => $this->text($site['not_found_title'] ?? null) ?? 'Page not found',
            'message' => $this->text($site['not_found_message'] ?? null) ?? 'The page you requested could not be found.',
            'button' => $this->text($site['not_found_button_label'] ?? null) ?? 'Return home',
            'canonical' => $this->currentUrl($request), 'robots' => 'noindex, follow', 'siteTitle' => $this->text($site['site_title'] ?? null) ?? $website->name,
        ];
    }

    /**
     * @param array<string, mixed> $site
     * @param array<string, mixed> $pageSeo
     * @param list<array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function payload(array $site, string $title, string $description, string $canonical, string $robots, ?string $image, array $pageSeo, array $schemas, bool $preview): array
    {
        return [
            'language' => $this->text($site['primary_language'] ?? null) ?? 'en', 'title' => $title, 'description' => $description,
            'canonical' => $canonical, 'robots' => $robots, 'openGraphTitle' => $this->text($pageSeo['open_graph_title'] ?? null) ?? $title,
            'openGraphDescription' => $this->text($pageSeo['open_graph_description'] ?? null) ?? $description, 'image' => $image,
            'googleVerification' => $this->text($site['google_site_verification'] ?? null),
            'analyticsId' => $preview ? null : $this->text($site['google_analytics_id'] ?? null),
            'openGraphType' => 'website',
            'jsonLd' => json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $page
     * @param array<string, mixed> $site
     * @param array<string, mixed> $pageSeo
     * @return list<array<string, mixed>>
     */
    private function pageSchemas(Website $website, array $snapshot, array $page, array $site, array $pageSeo, string $canonical, string $title, string $description, Request $request): array
    {
        $origin = $request->getSchemeAndHttpHost();
        $schemas = [[
            '@context' => 'https://schema.org', '@type' => (string) ($pageSeo['schema_type'] ?? 'WebPage'), '@id' => $canonical.'#webpage',
            'url' => $canonical, 'name' => $title, 'description' => $description, 'isPartOf' => ['@id' => $origin.'/#website'],
        ]];
        if (($page['is_home'] ?? false) === true) {
            $businessName = $this->text($site['business_name'] ?? null) ?? $website->name;
            $organization = ['@context' => 'https://schema.org', '@type' => (string) ($site['business_type'] ?? 'Organization'), '@id' => $origin.'/#organization', 'name' => $businessName, 'url' => $origin.'/'];
            $image = $this->text($site['default_social_image'] ?? null);
            if ($image !== null) $organization['logo'] = $image;
            $profiles = is_array($site['social_profiles'] ?? null) ? array_values(array_filter($site['social_profiles'], 'is_string')) : [];
            if ($profiles !== []) $organization['sameAs'] = $profiles;
            $schemas[] = $organization;
            $schemas[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => $origin.'/#website', 'url' => $origin.'/', 'name' => $this->text($site['site_title'] ?? null) ?? $website->name, 'publisher' => ['@id' => $origin.'/#organization']];
        } else {
            $schemas[] = $this->breadcrumbs($origin, [['name' => (string) ($page['title'] ?? $title), 'url' => $canonical]]);
        }
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        $faqSection = collect($sections)->first(fn (mixed $section): bool => is_array($section) && ($section['type'] ?? null) === 'faq' && ($section['is_enabled'] ?? false) === true);
        $modules = is_array($snapshot['content_modules'] ?? null) ? $snapshot['content_modules'] : [];
        $faqs = is_array($modules['faq'] ?? null) ? $modules['faq'] : [];
        if (is_array($faqSection) && $faqs !== []) {
            $faqContent = is_array($faqSection['content'] ?? null) ? $faqSection['content'] : [];
            $selectedFaqs = collect(preg_split('/\R/', (string) ($faqContent['faq_keys'] ?? '')) ?: [])->filter();
            $faqRecords = collect($faqs)->filter(fn (mixed $faq): bool => is_array($faq) && ($faq['is_active'] ?? true) === true && filled($faq['question'] ?? null) && filled($faq['answer'] ?? null))
                ->when(($faqContent['faq_selection_mode'] ?? 'all') === 'selected', fn ($collection) => $collection->filter(fn (array $faq): bool => $selectedFaqs->contains($faq['question'])))
                ->when(filled($faqContent['faq_category'] ?? null), fn ($collection) => $collection->filter(fn (array $faq): bool => ($faq['category'] ?? '') === $faqContent['faq_category']));
            $faqRecords = match ($faqContent['faq_sort'] ?? 'display-order') {
                'question' => $faqRecords->sortBy(fn (array $faq): string => mb_strtolower((string) $faq['question'])),
                'category' => $faqRecords->sortBy(fn (array $faq): string => mb_strtolower((string) ($faq['category'] ?? ''))),
                default => $faqRecords,
            };
            $questions = $faqRecords->take((int) ($faqContent['faq_limit'] ?? 12))
                ->map(fn (array $faq): array => ['@type' => 'Question', 'name' => strip_tags((string) $faq['question']), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $faq['answer'])]])->values()->all();
            if ($questions !== []) $schemas[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $questions];
        }
        $visibleTypes = collect($sections)->filter(fn (mixed $section): bool => is_array($section) && ($section['is_enabled'] ?? false) === true)
            ->pluck('type')->filter(fn (mixed $type): bool => is_string($type));
        foreach (['services' => 'Service', 'products' => 'Product'] as $module => $schemaType) {
            if (! $visibleTypes->contains($module)) {
                continue;
            }
            $items = is_array($modules[$module] ?? null) ? $modules[$module] : [];
            foreach ($items as $item) {
                $itemTitle = is_array($item) ? $this->text($item['title'] ?? $item['name'] ?? null) : null;
                if (! is_array($item) || ($item['is_active'] ?? true) !== true || $itemTitle === null) {
                    continue;
                }
                $entity = [
                    '@context' => 'https://schema.org', '@type' => $schemaType,
                    'name' => strip_tags($itemTitle),
                    'url' => $canonical.'#'.Str::slug($itemTitle),
                ];
                $summary = $this->text($item['short_description'] ?? $item['description'] ?? null);
                if ($summary !== null) {
                    $entity['description'] = strip_tags($summary);
                }
                $itemImage = $this->text($item['image'] ?? $item['featured_image'] ?? null);
                if ($itemImage !== null) {
                    $entity['image'] = $itemImage;
                }
                $schemas[] = $entity;
            }
        }

        return $schemas;
    }

    /**
     * @param array<string, mixed> $site
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function contentSchema(Website $website, array $site, string $type, array $item, string $url, string $title, string $description, ?string $image): array
    {
        $schema = ['@context' => 'https://schema.org', '@type' => match ($type) { 'blog' => 'Article', 'news' => 'NewsArticle', 'events' => 'Event', 'careers' => 'JobPosting', default => 'WebPage' }, 'url' => $url, 'name' => $title, 'description' => $description];
        if ($image !== null) $schema['image'] = $image;
        if (in_array($type, ['blog', 'news'], true)) {
            if (filled($item['published_at'] ?? null)) $schema['datePublished'] = (string) $item['published_at'];
            if (filled($item['author_name'] ?? null)) $schema['author'] = ['@type' => 'Person', 'name' => (string) $item['author_name']];
            $schema['headline'] = $title;
        } elseif ($type === 'events') {
            if (filled($item['event_start_date'] ?? null)) $schema['startDate'] = (string) $item['event_start_date'].(filled($item['event_start_time'] ?? null) ? 'T'.$item['event_start_time'] : '');
            if (filled($item['event_end_date'] ?? null)) $schema['endDate'] = (string) $item['event_end_date'].(filled($item['event_end_time'] ?? null) ? 'T'.$item['event_end_time'] : '');
            if (filled($item['venue'] ?? null)) $schema['location'] = ['@type' => 'Place', 'name' => (string) $item['venue'], 'address' => (string) ($item['address'] ?? '')];
        } elseif ($type === 'careers') {
            $schema['title'] = $title;
            if (filled($item['description'] ?? null)) $schema['description'] = strip_tags((string) $item['description']);
            if (filled($item['created_at'] ?? null)) $schema['datePosted'] = (string) $item['created_at'];
            if (filled($item['closing_date'] ?? null)) $schema['validThrough'] = (string) $item['closing_date'];
            if (filled($item['employment_type'] ?? null)) $schema['employmentType'] = strtoupper(str_replace([' ', '-'], '_', (string) $item['employment_type']));
            if (filled($item['location'] ?? null)) $schema['jobLocation'] = ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => (string) $item['location']]];
            $schema['hiringOrganization'] = [
                '@type' => 'Organization',
                'name' => $this->text($site['business_name'] ?? null) ?? $website->name,
            ];
        }

        return $schema;
    }

    /**
     * @param list<array{name: string, url: string}> $items
     * @return array<string, mixed>
     */
    private function breadcrumbs(string $origin, array $items): array
    {
        array_unshift($items, ['name' => 'Home', 'url' => $origin.'/']);

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url']])->all()];
    }

    /** @param array<string, mixed> $site */
    private function robots(array $site): string { return ($site['search_engine_visibility'] ?? true) === true ? 'index, follow' : 'noindex, nofollow, noarchive'; }

    /** @param list<string> $keepQuery */
    private function currentUrl(Request $request, array $keepQuery = []): string
    {
        $url = $request->getSchemeAndHttpHost().$request->getPathInfo();
        $query = Arr::only($request->query(), $keepQuery);

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    /** @return array<string, mixed> */
    private function map(mixed $value): array { return is_array($value) ? $value : []; }
    private function text(mixed $value): ?string { return is_string($value) && trim($value) !== '' ? trim($value) : null; }
}
